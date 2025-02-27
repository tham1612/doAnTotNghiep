<?php

namespace App\Http\Controllers;

// use function Laravel\Prompts\select;
//const PATH_UPLOAD = 'board';
use App\Enums\AuthorizeEnum;
use App\Events\EventNotification;
use App\Events\RealtimeBoardArchiver;
use App\Events\RealtimeBoardDetail;
use App\Events\UserInvitedToBoard;
use App\Models\Board;
use App\Models\BoardMember;
use App\Models\Catalog;
use App\Models\CheckList;
use App\Models\Color;
use App\Models\Follow_member;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\TaskMember;
use App\Models\TaskTag;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\TaskDueNotification;
use App\Notifications\TaskOverdueNotification;
use App\Notifications\WorkspaceNotification;
use App\Notifications\BoardMemberNotification;
use App\Notifications\BoardNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Models\WorkspaceMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class BoardController extends Controller
{
    const PATH_UPLOAD = 'boards';
    public $googleApiClient;
    public $catalogController;
    public $taskController;
    public $authorizeWeb;

    public function __construct(
        GoogleApiClientController $googleApiClient,
        CatalogControler $catalogController,
        TaskController $taskController,
        AuthorizeWeb $authorizeWeb
    ) {
        $this->googleApiClient = $googleApiClient;
        $this->catalogController = $catalogController;
        $this->taskController = $taskController;
        $this->authorizeWeb = $authorizeWeb;
    }

    /**
     * Display a listing of the resource.
     */


    public function index($workspaceId)
    {
        $userId = Auth::id();

        // Lấy tất cả các bảng trong workspace mà người dùng là người tạo hoặc là thành viên
        $boards = Board::where('workspace_id', $workspaceId)
            ->where(function ($query) use ($userId) {
                // Sửa điều kiện này để so sánh với trường lưu thông tin người tạo, ví dụ: 'created_by'
                $query->where('created_at', $userId)
                    ->orWhereHas('boardMembers', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                });
            })
            ->with(['workspace', 'boardMembers', 'catalogs.tasks']) // Tải các tasks liên quan
            ->get()
            ->map(function ($board) use ($userId) {
                // Tính tổng số thành viên trong bảng
                $board->total_members = $board->boardMembers->count();

                // Kiểm tra xem user có đánh dấu sao cho bảng này không
                $board->is_star = $board->boardMembers->contains(function ($member) use ($userId) {
                    return $member->user_id == $userId && $member->is_star == 1;
                });

                // Kiểm tra xem user có theo dõi bảng này không (follow = 1)
                $board->follow = $board->boardMembers->contains(function ($member) use ($userId) {
                    return $member->user_id == $userId && $member->follow == 1;
                });

                // Tính tổng số nhiệm vụ và tổng progress của các task trong bảng
                $totalTasks = $board->catalogs->pluck('tasks')->flatten()->count();
                $totalProgress = $board->catalogs->pluck('tasks')->flatten()->sum('progress');

                // Tính phần trăm tiến độ (progress)
                $board->complete = $totalTasks > 0 ? round($totalProgress / $totalTasks, 2) : 0;

                return $board;
            });

        // Lọc danh sách các bảng mà user đã đánh dấu sao
        $board_star = $boards->filter(function ($board) use ($userId) {
            return $board->boardMembers->contains(function ($member) use ($userId) {
                return $member->user_id == $userId && $member->is_star == 1;
            });
        });

        // Trả về view với danh sách bảng, bảng đã đánh dấu sao và workspaceId
        return view('homes.dashboard', compact('boards', 'board_star', 'workspaceId'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('b.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $authorize = $this->authorizeWeb->authorizeCreateBoardOnWorkspace();
        if (!$authorize) {
            session(['msg' => 'Bạn không có quyền!!']);
            session(['action' => 'danger']);
            return back();
        }

        $data = $request->except(['image', 'link_invite']);
        if ($request->hasFile('image')) {
            $data['image'] = Storage::put(self::PATH_UPLOAD, $request->file('image'));
        }
        $uuid = Str::uuid();
        $token = Str::random(40);
        $data['link_invite'] = url("taskflow/invite/b/{$uuid}/{$token}");
        try {
            DB::beginTransaction();
            $board = Board::query()->create($data);
            BoardMember::query()->create([
                'user_id' => auth()->id(),
                'board_id' => $board->id,
                'authorize' => 'Owner',
                'invite' => now(),
            ]);
            // ghi lại hoạt động của bảng
            activity('Thêm mới bảng')
                ->performedOn($board)
                ->causedBy(Auth::user())
                ->withProperties([
                    'workspace' => $board->workspace_id,
                    'board_id' => $board->id,
                ])
                ->tap(function (Activity $activity) use ($board) {
                    $activity->board_id = $board->id;
                    $activity->workspace_id = $board->workspace_id;
                })
                ->log('Người dùng đã thêm bảng mới.');

            DB::commit();
            session(['msg' => 'Thêm bảng ' . $data['name'] . ' thành công!']);
            session(['action' => 'success']);
            return redirect()->route('b.edit', $board->id);
        } catch (\Exception $exception) {
            DB::rollBack();
            return back()->with([
                'msg' => 'Error: ' . $exception->getMessage(),
                'action' => 'danger'
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $id)
    {

        $board = Board::query()->findOrFail($id);
        $colors = Color::query()->get();
        session([
            'board' => $board,
            'colors' => $colors,
        ]);
        $viewType = \request('viewType', 'board');
        // https://laravel.com/docs/10.x/eloquent-relationships#lazy-eager-loading
        // https://laravel.com/docs/10.x/eloquent-relationships#nested-eager-loading
        $board->load([
            'members',
            'tags',
            'users',
            'catalogs' => function ($query) use ($request) {
                $query->with([
                    'tasks' => function ($taskQuery) use ($request) {
                        $taskQuery->with([
                            'catalog:id,name',
                            'members',
                            'checkLists',
                            'checkLists.checkListItems',
                            'checkLists.checkListItems.checkListItemMembers',
                            'checkLists.checkListItems.checkListItemMembers.user',
                            'checkLists.checkListItems.members',
                            'tags',
                            'followMembers',
                            'attachments',
                            'taskComments',
                            'taskComments.user',
                        ])->where(function ($subQuery) use ($request) {
                            if (!empty($request->search)) {
                                $subQuery->where('text', 'LIKE', "%{$request->search}%");
                            }
                            // Điều kiện 1: Lọc thành viên
                            if ($request->has('no_member') || $request->has('it_me')) {
                                $subQuery->where(function ($query) use ($request) {
                                    if ($request->no_member) {
                                        $query->whereDoesntHave('members');
                                    }
                                    if ($request->it_me) {
                                        $query->orWhereHas('members', function ($memberQuery) {
                                            $memberQuery->where('user_id', auth()->id());
                                        });
                                    }
                                });
                            }

                            // Điều kiện 2: Lọc ngày hết hạn
                            if ($request->has('no_date') || $request->has('no_overdue') || $request->has('due_tomorrow')) {
                                $subQuery->where(function ($dateQuery) use ($request) {
                                    if ($request->no_date) {
                                        $dateQuery->whereNull('end_date');
                                    }
                                    if ($request->no_overdue) {
                                        $dateQuery->orWhere('end_date', '<', now());
                                    }
                                    if ($request->due_tomorrow) {
                                        $dateQuery->orWhere('end_date', '=', now()->addDay());
                                    }
                                });
                            }

                            // Điều kiện 3: Lọc theo nhãn
                            if ($request->has('no_tags') || $request->has('tags')) {
                                $subQuery->where(function ($tagQuery) use ($request) {
                                    if ($request->no_tags) {
                                        $tagQuery->doesntHave('tags');
                                    }
                                    if ($request->tags) {
                                        $tagQuery->whereHas('tags', function ($tagSubQuery) use ($request) {
                                            $tagSubQuery->whereIn('tags.id', $request->tags);
                                        });
                                    }
                                });
                            }
                        })
                            ->orderBy('position', 'asc');
                    }
                ]);
            }
        ]);

        if ($request->ajax()) {
            $viewType = $request->viewType;
            //            $this->middleware('csrf', ['except' => ['edit']]);
        }
        $boardMemberMain = BoardMember::query()
            ->join('users', 'users.id', '=', 'board_members.user_id')
            ->select('users.name', 'users.fullName', 'users.image', 'board_members.is_accept_invite', 'board_members.authorize', 'users.id as user_id', 'board_members.id as bm_id')
            ->where('board_members.board_id', $board->id)
            ->get();
        /*
         * pluck('tasks'): Lấy tất cả các tasks từ các catalogs, nó sẽ trả về một collection mà mỗi phần tử là một danh sách các tasks.
         * flatten(): Dùng để chuyển đổi một collection lồng vào nhau thành một collection phẳng, chứa tất cả các tasks.
         * */
        //        $boardId = $board->id; // ID của bảng mà bạn muốn xem hoạt động
        $activities = Activity::with('causer')
            ->where('properties->board_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();


        $catalogs = $board->catalogs;
        $tasks = $catalogs->pluck('tasks')->flatten();
        //        $board = Board::find($boardId); // Truy xuất thông tin của board từ bảng boards
//        $boardName = $board->name; // Lấy tên của board


        $boardMembers = $boardMemberMain->filter(function ($member) {
            return $member->authorize->value !== AuthorizeEnum::Owner()->value &&
                $member->authorize->value !== AuthorizeEnum::Sub_Owner()->value &&
                $member->is_accept_invite === 0;
        });
        $boardSubOwner = $boardMemberMain->filter(function ($member) {
            return $member->authorize->value == AuthorizeEnum::Sub_Owner()->value &&
                $member->is_accept_invite === 0;
        });
        $boardMemberInvites = $boardMemberMain->filter(function ($member) {
            return $member->is_accept_invite === 1;
        });

        // Lấy ra chủ sở hữu của bảng
        $boardOwner = $boardMemberMain->firstWhere('authorize', AuthorizeEnum::Owner()->value);
        //check mình có phải phó nhóm không
        $boardSubOwnerChecked = $boardMemberMain->filter(function ($member) {
            return $member->authorize == AuthorizeEnum::Sub_Owner()->value && $member->user_id == Auth::id();
        })->first();
        $boardMemberChecked = $boardMemberMain->filter(function ($member) {
            return $member->user_id == Auth::id();
        })->first();
        // Lấy danh sách thành viên của workspace mà chưa phải là thành viên của bảng
        $wspMember = WorkspaceMember::query()
            ->join('users', 'users.id', '=', 'workspace_members.user_id')
            ->leftJoin('board_members', function ($join) use ($board) {
                $join->on('workspace_members.user_id', '=', 'board_members.user_id')
                    ->where('board_members.board_id', '=', $board->id);
            })
            ->select('users.id', 'users.name')
            ->whereNull('board_members.user_id') // Thành viên chưa có trong bảng
            ->where('workspace_members.workspace_id', $board->workspace_id)
            ->where('workspace_members.authorize', '!=', 'Viewer') // Lọc những người không phải Viewer
            ->get();

        switch ($viewType) {
            case 'dashboard':
                return view('homes.dashboard_board', compact('board', 'activities', 'boardMembers', 'boardMemberInvites', 'boardOwner', 'wspMember', 'boardSubOwner', 'boardSubOwnerChecked', 'boardMemberChecked', 'id'));

            case 'list':
                return view('lists.index', compact('board', 'activities', 'boardMembers', 'boardMemberInvites', 'boardOwner', 'wspMember', 'boardSubOwner', 'boardSubOwnerChecked', 'boardMemberChecked'));

            case 'gantt':
                return view('ganttCharts.index', compact('board', 'activities', 'boardMembers', 'boardMemberInvites', 'boardOwner', 'wspMember', 'colors', 'tasks', 'boardSubOwner', 'boardSubOwnerChecked', 'boardMemberChecked'));


            case 'table':
                return view('tables.index', compact('board', 'activities', 'boardMembers', 'boardMemberInvites', 'boardOwner', 'wspMember', 'boardSubOwner', 'boardSubOwnerChecked', 'boardMemberChecked'));

            case 'calendar':
                $listEvent = array();

                $taskCalendar = Task::query()
                    ->whereHas('catalog', function ($query) use ($id) {
                        $query->where('board_id', $id);
                    })
                    ->get()
                    ->filter(function ($task) {
                        // Nếu cả hai đều không tồn tại, ẩn
                        if (is_null($task->start_date) && is_null($task->end_date)) {
                            return false;
                        }

                        // Nếu chỉ tồn tại một trong hai, gán giá trị của cái còn lại
                        if (is_null($task->start_date)) {
                            $task->start_date = $task->end_date;
                        } elseif (is_null($task->end_date)) {
                            $task->end_date = $task->start_date;
                        }
                        // Hiển thị task nếu đã xử lý xong
                        return true;
                    });
                foreach ($taskCalendar as $event) {
                    $listEvent[] = [
                        'id' => $event->id,
                        'id_google_calendar' => $event->id_google_calendar,
                        'title' => $event->text,
                        'email' => $event->creator_email,
                        'start' => Carbon::parse($event->start_date)->toIso8601String(),
                        'end' => Carbon::parse($event->end_date)->toIso8601String(),
                    ];
                }
                return view('calendars.index', compact('listEvent', 'board', 'activities', 'boardMembers', 'boardMemberInvites', 'boardOwner', 'wspMember', 'boardSubOwner', 'boardSubOwnerChecked', 'boardMemberChecked'));

            default:
                return view('boards.index', compact('board', 'activities', 'boardMembers', 'boardMemberInvites', 'boardOwner', 'wspMember', 'boardSubOwner', 'boardSubOwnerChecked', 'boardMemberChecked'));
        }
    }


    public function filter(Request $request, string $boardId)
    {
        $filters = $request->all(); // Lấy tất cả dữ liệu từ form

        // Thực hiện lọc các task hoặc dữ liệu theo yêu cầu
        $filteredTasks = Task::query()
            ->when(isset($filters['search']), function ($query) use ($filters) {
                return $query->where('text', 'like', '%' . $filters['search'] . '%');
            })
            ->when(isset($filters['no_member']), function ($query) {
                return $query->doesntHave('members');
            })
            ->when(isset($filters['due_tomorrow']), function ($query) {
                return $query->whereDate('due_date', '=', now()->addDay());
            })
            // Add thêm các điều kiện lọc khác
            ->get();


        return response()->json([
            'success' => true,
            'filteredTasks' => $filteredTasks
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //        dd($request->all());
        $authorize = $this->authorizeWeb->authorizeEdit($id);
        if (!$authorize) {
            return response()->json([
                'action' => 'error',
                'msg' => 'Bạn không có quyền!!',
            ]);
        }
        $board = Board::query()->findOrFail($id);
        $data = $request->except(['_token', '_method', 'image']);
        if ($request->hasFile('image')) {
            $imagePath = Storage::put(self::PATH_UPLOAD, $request->file('image'));
            $data['image'] = $imagePath;
            if ($board->image && Storage::exists($board->image)) {
                Storage::delete($board->image);
            }
        }

        $board->update($data);
        broadcast(new RealtimeBoardDetail($board, $board->id))->toOthers();
        return response()->json([
            'msg' => $board['name'] . '  cập nhật thông tin thành công!',
            'action' => 'success',
            'board' => $board
        ]);
    }


    public function updateBoardMember(Request $request, string $id)
    {
        $data = $request->only(['user_id']);

        $boardMember = BoardMember::where('board_id', $id)->where('user_id', $data['user_id'])->first();

        if ($boardMember) {
            $boardMember->update(['is_star' => !$boardMember->is_star]);

            return response()->json([
                'msg' => 'Người dùng cập nhật dấu sao bảng thành công',
                'action' => 'success'
            ]);
        }

        return response()->json([
            'msg' => 'Không tồn tại người dùng trong bảng',
            'action' => 'error'
        ]);
    }

    //    public function updateBoardMember2(Request $request, string $id)
//    {
//        $data = $request->only(['user_id', 'board_id']);
//
//
//        $boardMember = BoardMember::where('board_id', $data['board_id'])
//            ->where('user_id', $data['user_id'])
//            ->first();
//
//        if ($boardMember) {
//            $newFollow = $boardMember->follow == 1 ? 0 : 1;
//            $boardMember->update(['follow' => $newFollow]);
//
//            return response()->json([
//                'follow' => $boardMember->follow, // Trả về trạng thái follow mới
//            ]);
//        }
//    }

    //Duyệt người dùng gửi lời mời vào board
    //thông báo done
    public function acceptMember(Request $request)
    {
        if (session('view_only', false)) {
            return back()->with('error', 'Bạn chỉ có quyền xem và không thể chỉnh sửa bảng này.');
        }
        session()->forget('view_only');

        $user = User::find($request->user_id);
        $board = Board::find($request->board_id);
        Log::debug($user);
        Log::debug($board);

        $checkUser = WorkspaceMember::where('user_id', $request->user_id)
            ->where('workspace_id', $board->workspace_id)
            ->first();
        $owner = BoardMember::where('authorize', "Owner")
            ->where('board_id', $request->board_id)
            ->first();
        try {
            BoardMember::query()
                ->where('user_id', $request->user_id)
                ->where('board_id', $request->board_id)
                ->update([
                    'is_accept_invite' => 0,
                ]);
            if (empty($checkUser)) {
                WorkspaceMember::create([
                    'user_id' => $request->user_id,
                    'workspace_id' => $board->workspace_id,
                    'authorize' => "Viewer",
                    'invite' => now(),
                    'is_active' => 0,
                ]);
            }

            $this->notificationMemberInviteBoard($board->id, $user->name);
            return response()->json([
                'success' => true,
                'action' => 'success',
                'msg' => 'bạn đã chấp nhận người dùng vào bảng',
                'name' => $user->name,
                'image' => $user->image ? Storage::url($user->image) : null,
                'owner_id' => $owner->id
            ]);

        } catch (\Exception $e) {
            throw $e;
        }
    }

    //Từ chối người dùng gửi lời mời vào board
    //thông báo  done
    public function refuseMember($bm_id)
    {
        $boardMember = BoardMember::with(['user', 'board'])->find($bm_id);

        if (session('view_only', false)) {
            return back()->with('error', 'Bạn chỉ có quyền xem và không thể chỉnh sửa bảng này.');
        }

        session()->forget('view_only');

        try {
            $title = "Phản hồi về lời mời tham gia bảng";
            $description = "Rất tiếc, lời mời tham gia bảng " . $boardMember->board->name . " của bạn chưa được phê duyệt. Cảm ơn bạn đã quan tâm, và hy vọng sẽ có cơ hội hợp tác trong các dự án khác!";

            if ($boardMember->user->id == Auth::id()) {
                event(new EventNotification("Rất tiếc, lời mời tham gia bảng " . $boardMember->board->name . " của bạn chưa được phê duyệt", 'success', $boardMember->user->id));
            }

            $boardMember->user->notify(new BoardMemberNotification($title, $description, $boardMember->board->name, $boardMember->user->name));
            $boardMember->forceDelete();

            return response()->json([
                'success' => true,
                'action' => 'success',
                'msg' => 'Bạn đã từ chối người dùng vào bảng.',
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    //Kích thành viên || Rời khỏi bảng
    //thông báo done

    public function activateMember($boardMemberId)
    {
        if (session('view_only', false)) {
            return back()->with('error', 'Bạn chỉ có quyền xem và không thể chỉnh sửa bảng này.');
        }
        session()->forget('view_only');
        //lấy được thằng boardmember đang bị xóa || lấy được cả thằng boardID || lấy được cả wspID
        $boardMember = BoardMember::where('id', $boardMemberId)->with('board', 'user')->first();
        try {

            if ($boardMember) {
                $title = "Rời khỏi bảng công việc";
                $description = 'Rất tiếc, bạn đã bị loại khỏi bảng "' . $boardMember->board->name . '" trong không gian làm việc. Chúng tôi hy vọng sẽ có cơ hội làm việc cùng bạn trong tương lai!';
                $boardMember->user->notify(new BoardMemberNotification($title, $description, $boardMember->board->name, $boardMember->user->name));
                $this->notificationAcceptMemberBoard($boardMember->board->id, $boardMember->user->name);
                // if ($boardMember->user->id == Auth::id()) {
                event(new EventNotification("Rất tiếc, bạn đã bị loại khỏi bảng", 'success', $boardMember->user->id, true));
                // }

                $catalogs = Catalog::where('board_id', $boardMember->board_id)->get(); // Nếu đã định nghĩa quan hệ hasMany
                foreach ($catalogs as $catalog) {
                    $tasks = $catalog->tasks; // Quan hệ hasMany

                    foreach ($tasks as $task) {

                        $taskMembers = TaskMember::where('task_id', $task->id)->where('user_id', $boardMember->user_id)->forceDelete();
                        $checkLists = CheckList::where('task_id', $task->id)->get();

                        foreach ($checkLists as $checkList) {

                            foreach ($checkList->checkListItems as $checkListItem) {

                                foreach ($checkListItem->checkListItemMembers as $member) {
                                    $member->forceDelete();
                                }
                            }
                        }
                    }
                }
                $boardMember->forceDelete();
                if (request()->ajax()) {
                    return response()->json([
                        'success' => true,
                        'action' => 'success',
                        'msg' => 'Bạn đã kích thành viên khỏi bảng ' . $boardMember->board->name
                    ]);
                }
            } else {
                if (request()->ajax()) {
                    return response()->json([
                        'success' => false,
                        'action' => 'error',
                        'msg' => 'Thành viên đã không còn ở bảng ' . $boardMember->board->name
                    ]);
                }
            }

        } catch (\Throwable $th) {
            //throw $th;
        }

    }


    public function leaveBoard($boardMemberId)
    {
        if (session('view_only', false)) {
            return back()->with('error', 'Bạn chỉ có quyền xem và không thể chỉnh sửa bảng này.');
        }
        session()->forget('view_only');
        //lấy được thằng boardmember đang bị xóa || lấy được cả thằng boardID || lấy được cả wspID
        $boardMember = BoardMember::where('id', $boardMemberId)->with('board', 'user')->first();

        if ($boardMember) {

            //owner rời khỏi bảng
            if ($boardMember->authorize->value == 'Owner') {
                $boardCheck = Board::find($boardMember->board_id)->with('user')->first();
                if ($boardCheck->users->count() == 1) {
                    $title = "Rời khỏi bảng công việc";
                    $description = 'Rất tiếc, bạn đã rời khỏi bảng "' . $boardMember->board->name . '" trong không gian làm việc. Chúng tôi hy vọng sẽ có cơ hội làm việc cùng bạn trong tương lai!';
                    $boardMember->user->notify(new BoardMemberNotification($title, $description, $boardMember->board->name, $boardMember->user->name));
                    $this->notificationAcceptMemberBoard($boardMember->board->id, $boardMember->user->name);
                    if ($boardMember->user->id == Auth::id()) {
                        event(new EventNotification("Bạn đã rời khỏi bảng", 'success', $boardMember->user->id));
                    }


                    $catalogs = Catalog::where('board_id', $boardMember->board_id)->get(); // Nếu đã định nghĩa quan hệ hasMany
                    foreach ($catalogs as $catalog) {
                        $tasks = $catalog->tasks; // Quan hệ hasMany

                        foreach ($tasks as $task) {

                            $taskMembers = TaskMember::where('task_id', $task->id)->where('user_id', $boardMember->user_id)->forceDelete();
                            $checkLists = CheckList::where('task_id', $task->id)->get();

                            foreach ($checkLists as $checkList) {

                                foreach ($checkList->checkListItems as $checkListItem) {

                                    foreach ($checkListItem->checkListItemMembers as $member) {
                                        $member->forceDelete();
                                    }
                                }
                            }
                        }
                    }

                    $boardMember->forceDelete();

                    if (request()->ajax()) {
                        return response()->json([
                            'success' => true,
                            'action' => 'success',
                            'msg' => 'Bạn đã rời khỏi bảng ' . $boardMember->board->name
                        ]);
                    }

                    return redirect()->route('home')->with([
                        'msg' => "Bạn đã rời khỏi bảng ",
                        'action' => 'success'
                    ]);
                } else {

                    if (request()->ajax()) {
                        return response()->json([
                            'success' => true,
                            'action' => 'error',
                            'msg' => 'Bạn phải nhượng quyền cho người khác trước khi rời khỏi bảng'
                        ]);
                    }

                    return back()->with([
                        'msg' => "Bạn phải nhượng quyền cho người khác trước khi rời khỏi bảng",
                        'action' => 'danger'
                    ]);
                }
            } //member và sub owner rời khỏi bảng
            else {
                $title = "Rời khỏi bảng công việc";
                $description = 'Rất tiếc, bạn rời khỏi bảng "' . $boardMember->board->name . '" trong không gian làm việc. Chúng tôi hy vọng sẽ có cơ hội làm việc cùng bạn trong tương lai!';
                $boardMember->user->notify(new BoardMemberNotification($title, $description, $boardMember->board->name, $boardMember->user->name));
                $this->notificationAcceptMemberBoard($boardMember->board->id, $boardMember->user->name);
                if ($boardMember->user->id == Auth::id()) {
                    event(new EventNotification("Rất tiếc, bạn đã rời khỏi bảng", 'success', $boardMember->user->id));
                }

                $catalogs = Catalog::where('board_id', $boardMember->board_id)->get(); // Nếu đã định nghĩa quan hệ hasMany
                foreach ($catalogs as $catalog) {
                    $tasks = $catalog->tasks; // Quan hệ hasMany

                    foreach ($tasks as $task) {

                        $taskMembers = TaskMember::where('task_id', $task->id)->where('user_id', $boardMember->user_id)->forceDelete();
                        $checkLists = CheckList::where('task_id', $task->id)->get();

                        foreach ($checkLists as $checkList) {

                            foreach ($checkList->checkListItems as $checkListItem) {

                                foreach ($checkListItem->checkListItemMembers as $member) {
                                    $member->forceDelete();
                                }
                            }
                        }
                    }
                }

                $boardMember->forceDelete();

                if (request()->ajax()) {
                    return response()->json([
                        'success' => true,
                        'action' => 'success',
                        'msg' => 'Bạn đã rời khỏi bảng ' . $boardMember->board->name
                    ]);
                }

                return redirect()->route('home')->with([
                    'msg' => "Bạn đã rời khỏi bảng ",
                    'action' => 'success'
                ]);
            }
        } else {
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'action' => 'success',
                    'msg' => 'Bạn đã rời khỏi bảng ' . $boardMember->board->name
                ]);
            }

            return redirect()->route('home')->with([
                'msg' => "Bạn đã không còn ở bảng ",
                'action' => 'warning'
            ]);
        }


    }

    //Thăng cấp thành viên
    //thông báo done
    public function upgradeMemberShip($boardMemberId)
    {
        $boardMember = BoardMember::with(['user', 'board'])->find($boardMemberId);
        if (session('view_only', false)) {
            return back()->with('error', 'Bạn chỉ có quyền xem và không thể chỉnh sửa bảng này.');
        }
        session()->forget('view_only');
        BoardMember::find($boardMemberId)->update([
            'authorize' => AuthorizeEnum::Sub_Owner()
        ]);
        $this->notificationUpgradeMemberShipBoard($boardMember->board->id, $boardMember->user->name);
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'msg' => 'Thành viên đã được thăng cấp thành công!',
                'action' => 'success',
                'name' => $boardMember->user->name
            ]);
        }
        return back()->with([
            'msg' => 'Bạn đã thăng cấp thành viên thành công',
            'action' => 'success'
        ]);
    }

    //Nhượng quyền
    //thông báo done
    public function managementfranchise($boardOwnerId, $boardUserId)
    {
        $boardMember = BoardMember::with(['user', 'board'])->find($boardUserId);
        try {
            BoardMember::find($boardUserId)->update([
                'authorize' => AuthorizeEnum::Owner()
            ]);

            BoardMember::find($boardOwnerId)->update([
                'authorize' => AuthorizeEnum::Member()
            ]);
            $this->notificationManagementfranchiseBoard($boardMember->board->id, $boardMember->user->name);
            return back()->with([
                'msg' => 'Bạn đã nhượng quyền quản trị viên',
                'action' => 'warning'
            ]);
        } catch (\Throwable $th) {
            dd($th);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $authorize = $this->authorizeWeb->authorizeArchiver($id);
        if (!$authorize) {
            return response()->json([
                'action' => 'error',
                'msg' => 'Bạn không có quyền!!',
            ]);
        }
        $board = Board::query()->findOrFail($id);
        $catalogsId = Catalog::query()
            ->where('board_id', $id)
            ->get()
            ->pluck('id')
            ->toArray();
        try {
            DB::beginTransaction();
            foreach ($catalogsId as $catalogId) {
                $this->catalogController->destroy($catalogId);
            }
            $board->delete();

            DB::commit();
            broadcast(new RealtimeBoardArchiver($board, $board->id))->toOthers();
            return response()->json([
                'action' => 'success',
                'msg' => 'Lưu trữ bảng thành công!!'
            ]);
        } catch (\Exception $e) {
            dd($e->getMessage());
            return response()->json([
                'action' => 'error',
                'msg' => 'Có lỗi xảy ra!!'
            ]);
        }
    }

    public function destroyBoard(string $id)
    {
        $authorize = $this->authorizeWeb->authorizeArchiver($id);
        if (!$authorize) {
            return response()->json([
                'action' => 'error',
                'msg' => 'Bạn không có quyền!!',
            ]);
        }

        Log::debug('board work');
        $board = Board::withTrashed()->findOrFail($id);
        $catalogsId = Catalog::withTrashed()
            ->where('board_id', $id)
            ->pluck('id');

        try {
            DB::beginTransaction();

            BoardMember::query()->where('board_id', $board->id)->delete();

            foreach ($catalogsId as $catalogId) {
                $catalog = Catalog::withTrashed()->findOrFail($catalogId);
                $tasksId = Task::withTrashed()
                    ->where('catalog_id', $catalogId)
                    ->pluck('id');
                foreach ($tasksId as $taskId) {
                    // đơn
                    Follow_member::query()->where('task_id', $taskId)->delete();
                    TaskMember::query()->where('task_id', $taskId)->delete();
                    TaskTag::query()->where('task_id', $taskId)->delete();
                    TaskAttachment::query()->where('task_id', $taskId)->delete();
                    $task = Task::withTrashed()->findOrFail($taskId);
                    foreach ($task->checkLists as $checklist) {
                        // Lặp qua các checklist item của mỗi checklist và xóa các item members
                        foreach ($checklist->checkListItems as $checklistItem) {
                            $checklistItem->checkListItemMembers()->delete();
                        }
                        // Xóa tất cả các checklist items của checklist
                        $checklist->checkListItems()->delete();
                    }

                    TaskComment::query()->where('task_id', $taskId)->forceDelete();

                    //  kết hợp
                    CheckList::query()->where('task_id', $taskId)->delete();

                    $task->forceDelete();
                    if ($task->id_google_calendar)
                        $this->googleApiClient->deleteEvent($task->id_google_calendar);
                }

                $catalog->forceDelete();
            }
            //            $tagIds = Tag::where('board_id', $board->id)->pluck('id');
//            TaskTag::whereIn('tag_id', $tagIds)->delete();

            Tag::where('board_id', $board->id)->delete();
            $board->forceDelete();

            DB::commit();

            return response()->json([
                'action' => 'success',
                'msg' => 'Xóa bảng thành công!!',
                'board' => $board
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e->getMessage());
            return response()->json([
                'action' => 'error',
                'msg' => 'Có lỗi xảy ra!!'
            ]);
        }

    }


    public function restoreBoard(string $id)
    {

        $authorize = $this->authorizeWeb->authorizeArchiver($id);
        if (!$authorize) {
            return response()->json([
                'action' => 'error',
                'msg' => 'Bạn không có quyền!!',
            ]);
        }
        $board = Board::withTrashed()->findOrFail($id);
        $catalogs = Catalog::withTrashed()
            ->where('board_id', $id)
            ->get();

        try {
            DB::beginTransaction();

            foreach ($catalogs as $catalog) {
                if ($board->deleted_at == $catalog->deleted_at) {
                    $this->catalogController->restoreCatalog($catalog->id);
                }
            }

            $board->restore();

            DB::commit();
            return response()->json([
                'action' => 'success',
                'msg' => 'Hoàn tác bảng thành công!!',
                'board' => $board,
            ]);
        } catch (\Exception $e) {
            dd($e->getMessage());
            return response()->json([
                'action' => 'error',
                'msg' => 'Có lỗi xảy ra!!'
            ]);
        }

    }

    public function copyBoard(Request $request)
    {
        //        dd($request->id);
        $authorize = $this->authorizeWeb->authorizeCopyBoardOnWorkspace($request->id);
        if (!$authorize) {
            return response()->json([
                'action' => 'error',
                'msg' => 'Bạn không có quyền!!',
            ]);
        }
        $data = $request->all();
        $uuid = Str::uuid();
        $token = Str::random(40);
        $data['link_invite'] = url("taskflow/invite/b/{$uuid}/{$token}");
        try {
            DB::beginTransaction();
            $boardNew = Board::query()->create($data);

            BoardMember::query()->create([
                'user_id' => auth()->id(),
                'board_id' => $boardNew->id,
                'authorize' => 'Owner',
                'invite' => now(),
            ]);
            $tagMap = [];

            if (isset($data['isTag'])) {
                $tagOld = Tag::query()->where('board_id', $data['id'])->get();
                foreach ($tagOld as $tag) {
                    $newTag = Tag::query()->create([
                        'board_id' => $boardNew->id,
                        'color_code' => $tag['color_code'],
                        'name' => $tag['name'],
                    ]);
                    $tagMap[$tag->id] = $newTag->id;
                }
            }
            if (isset($data['isCatalog'])) {
                $catalogOld = Catalog::query()->where('board_id', $data['id'])->get();
                foreach ($catalogOld as $catalog) {
                    $catalogNew = Catalog::query()->create([
                        'board_id' => $boardNew->id,
                        'name' => $catalog['name'],
                        'image' => $catalog['image'],
                        'position' => $catalog['position'],
                    ]);
                    $taskOld = Task::query()->where('catalog_id', $catalog['id'])->get();
                    if ($taskOld->isNotEmpty()) {
                        foreach ($taskOld as $task) {
                            $taskNew = Task::query()->create([
                                'catalog_id' => $catalogNew->id,
                                'text' => $task['text'],
                                'description' => $task['description'],
                                'position' => $task['position'],
                                'image' => $task['image'],
                                'priority' => $task['priority'],
                                'risk' => $task['risk'],
                                'progress' => $task['progress'],
                                'start_date' => $task['start_date'],
                                'end_date' => $task['end_date'],
                                'parent' => $task['parent'],
                                'sortorder' => $task['sortorder'],
                                'id_google_calendar' => $task['id_google_calendar'],
                                'creator_email' => Auth::user()->email,
                            ]);

                            //                            xử lý thêm tag vào từng task
                            if (isset($data['isTag'])) {
                                $taskTagOld = TaskTag::query()->where('task_id', $task['id'])->get();

                                foreach ($taskTagOld as $taskTag) {
                                    if (isset($tagMap[$taskTag->tag_id])) {
                                        TaskTag::query()->create([
                                            'task_id' => $taskNew->id,
                                            'tag_id' => $tagMap[$taskTag->tag_id], // Use the new Tag ID
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }

            }
            // ghi lại hoạt động của bảng
            activity('Người dùng đã tạo bảng ')
                ->performedOn($boardNew) // đối tượng liên quan là bảng vừa tạo
                ->causedBy(Auth::user()) // ai là người thực hiện hoạt động này
                ->withProperties(['workspace_id' => $boardNew->workspace_id]) // Lưu trữ workspace_id vào properties
                ->log('Đã tạo bảng mới: ' . $boardNew->name); // Nội dung ghi log


            DB::commit();
            return response()->json([
                'action' => 'success',
                'msg' => 'Sao chép bảng thành công!!',
                'board_id' => $boardNew->id
            ]);
        } catch (\Exception $e) {
            dd($e->getMessage());
            return response()->json([
                'action' => 'error',
                'msg' => 'Có lỗi xảy ra!!'
            ]);
        }
    }

    public function settingBoard(Request $request, string $id)
    {
        $authorize = $this->authorizeWeb->authorizeEditPermissionBoard($id);
        if (!$authorize) {
            return response()->json([
                'action' => 'error',
                'msg' => 'Bạn không có quyền!!',
            ]);
        }
        $board = Board::query()->findOrFail($id);

        if ($request->permissionType === 'commentPermission') {
            $board->update([
                'comment_permission' => $request->value
            ]);
        }
        if ($request->permissionType === 'memberPermission') {
            $board->update([
                'member_permission' => $request->value
            ]);
        }
        if ($request->permissionType === 'archivePermission') {
            $board->update([
                'archiver_permission' => $request->value
            ]);
        }
        if ($request->permissionType === 'boardEditPermission') {
            $board->update([
                'edit_board' => $request->value
            ]);
        }
        if ($request->permissionType === 'access') {
            $board->update([
                'access' => $request->value
            ]);
        }
        if ($request->permissionType === 'name') {
            $board->update([
                'name' => $request->value
            ]);
        }
        broadcast(new RealtimeBoardDetail($board, $board->id))->toOthers();
        return response()->json([
            'action' => 'success',
            'msg' => 'Thay đổi quyền thành công!!',
            'board' => $board
        ]);
    }

    public function getDataBoard(Request $request)
    {
        $boardId = $request->board_id;

        $board = Board::with('catalogs.tasks')->findOrFail($boardId);

        $catalogs = $board->catalogs->map(function ($catalog) {
            return [
                'id' => $catalog->id,
                'name' => $catalog->name,
                'task_count' => $catalog->tasks->count(),
            ];
        });
        return response()->json(['catalogs' => $catalogs]);
    }

    // gửi mail thêm người vào bảng
    public
        function inviteUserBoard(
        Request $request

    ) {
        $authorize = $this->authorizeWeb->authorizeDeleteCreateMember($request->id);
        if (!$authorize) {
            //            return response()->json([
//                'action' => 'error',
//                'msg' => 'Bạn không có quyền!!',
//            ]);
            session(['msg' => 'Bạn không có quyền!!']);
            session(['action' => 'danger']);
            return back();

        }
        $boardId = $request->id;
        $board = Board::query()
            ->where('id', $boardId)
            ->firstOrFail();

        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->input('email');
        $linkInvite = $board->link_invite;
        $boardName = $board->name;
        $authorize = $request->input('authorize');

        $user = User::where('email', $email)->first();
        if ($user) {
            $userCheck = BoardMember::where('user_id', $user->id)->where('board_id', $board->id)->first();
            if (!empty($userCheck)) {
                return response()->json([
                    'action' => 'error',
                    'msg' => 'Thành viên đã tồn tại ở trong bảng'
                ]);
            }
        }


        event(new UserInvitedToBoard($boardName, $email, $linkInvite, $authorize));
        return response()->json([
            'action' => 'success',
            'msg' => 'Đã gửi email thêm thành viên !!!'
        ]);
    }

    //người dùng tham gia vào bảng
//thông báo done
    public
        function acceptInviteBoard(
        $uuid,
        $token,
        Request $request
    ) {
        //xử lý khi admin gửi link invite cho người dùng
        if ($request->email) {
            $board = Board::where('link_invite', 'LIKE', "%$uuid/$token%")->first();
            if (!$board) {
                return redirect('/boardError');
            }
            $user = User::query()->where('email', $request->email)->first();
            $timestamp = $request->query('timestamp');
            $linkTime = Carbon::createFromTimestamp($timestamp);
            $currentTime = Carbon::now();

            if ($user) {
                $checkedUser = BoardMember::where('workspace_id', $board->id)
                    ->where('user_id', $user->id)->first();

                if ($checkedUser) {
                    abort(404, 'Link mời của bạn đã hết hạn');
                }
            }

            if ($currentTime->diffInSeconds($linkTime) > 300) {
                abort(404, 'Link mời của bạn đã hết hạn');
            }
            //xử lý khi người dùng có tài khoản
            if ($user) {
                $check_user_wsp = WorkspaceMember::where('user_id', $user->id)->where('workspace_id', $board->workspace_id)
                    ->first();
                $check_user_board = BoardMember::where('user_id', $user->id)->where('board_id', $board->id)
                    ->first();

                //Check xử lý người dùng có trong workspace
                if ($check_user_wsp) {

                    //xử lý khi người dùng chưa có trong bảng đó
                    if (!$check_user_board) {
                        //xử lý khi người dùng đã có tài khoản và đang đăng nhập
                        if (Auth::check()) {
                            $user_check = Auth::user(); // Lấy thông tin người dùng hiện tại

                            //xử lý người dùng khi đã đăng nhập đúng người dùng
                            if ($user_check->email === $request->email) {

                                try {
                                    //thêm người dùng vào board member
                                    BoardMember::create([
                                        'user_id' => $user_check->id,
                                        'board_id' => $board->id,
                                        'authorize' => $request->authorize,
                                        'invite' => now(),
                                    ]);
                                    $this->notificationMemberInviteBoard($board->id, $user_check->name);
                                    // ghi lại hoạt động thêm người vào ws
                                    activity('Member Added to Bảng')
                                        ->causedBy(Auth::user()) // Người thực hiện hành động
                                        ->performedOn($board) // Liên kết với bảng
                                        ->withProperties(['member_name' => $user_check->name]) // Thông tin bổ sung
                                        ->log('Người dùng đã được thêm vào Bảng.');

                                    session(['msg' => 'Bạn đã được thêm vào bảng. \"{$board->name}\" !!!']);
                                    session(['action' => 'success']);
                                    return redirect()->route('b.edit', $board->id);
                                } catch (\Throwable $th) {
                                    throw $th;
                                }
                            } // Người dùng đã đăng nhập nhưng email khác
                            else {
                                Auth::logout();
                                Session::put('invited_board', "case1");
                                Session::put('board_id', $board->id);
                                Session::put('user_id', $user->id);
                                Session::put('email_invited', $request->email);
                                Session::put('authorize', $request->authorize);
                                return redirect()->route('login');
                            }
                        } //xử lý khi người dùng có tài khoản rồi mà chưa đăng nhập
                        else {
                            Session::put('invited_board', "case1");
                            Session::put('board_id', $board->id);
                            Session::put('user_id', $user->id);
                            Session::put('email_invited', $request->email);
                            Session::put('authorize', $request->authorize);
                            return redirect()->route('login');
                        }
                    } //DONE

                    //xử lý khi người dùng đã có trong bảng đó rồi
                    else {
                        session(['msg' => 'Bạn đã ở trong bảng rồi!!']);
                        session(['action' => 'error']);
                        return redirect()->route('b.edit', $board->id);
                    }
                } //check xử lý nếu người dùng chưa ở trong wsp
                else {

                    //xử lý khi người dùng chưa có trong bảng đó
                    if (!$check_user_board) {
                        //xử lý khi người dùng đã có tài khoản và đang đăng nhập
                        if (Auth::check()) {

                            $user_check = Auth::user(); // Lấy thông tin người dùng hiện tại

                            //xử lý người dùng khi đã đăng nhập đúng người dùng
                            if ($user_check->email === $request->email) {
                                try {
                                    //thêm người dùng vào workspace member
                                    WorkspaceMember::create([
                                        'user_id' => $user_check->id,
                                        'workspace_id' => $board->workspace_id,
                                        'authorize' => AuthorizeEnum::Viewer(),
                                        'invite' => now(),
                                        'is_active' => 1,
                                    ]);

                                    //thêm người dùng vào workspace member
                                    BoardMember::create([
                                        'user_id' => $user_check->id,
                                        'board_id' => $board->id,
                                        'authorize' => $request->authorize,
                                        'invite' => now(),
                                    ]);

                                    //query workspace_member vừa tạo
                                    $wm = WorkspaceMember::query()
                                        ->where('user_id', $user_check->id)
                                        ->where('workspace_id', $board->workspace_id)
                                        ->first();

                                    //xử lý update is_active
                                    WorkspaceMember::query()
                                        ->where('user_id', $user_check->id)
                                        ->whereNot('id', $wm->id)
                                        ->update(['is_active' => 0]);
                                    WorkspaceMember::query()
                                        ->where('id', $wm->id)
                                        ->update(['is_active' => 1]);
                                    $this->notificationMemberInviteBoard($board->id, $user_check->name);
                                    // ghi lại hoạt động thêm người vào ws
                                    activity('Member Added to Bảng')
                                        ->causedBy(Auth::user()) // Người thực hiện hành động
                                        ->performedOn($board) // Liên kết với workspace
                                        ->withProperties(['member_name' => $user_check->name]) // Thông tin bổ sung
                                        ->log('Người dùng đã được thêm vào Bảng.');

                                    session(['msg' => 'Bạn đã được thêm vào bảng. \"{$board->name}\" !!!']);
                                    session(['action' => 'success']);
                                    return redirect()->route('b.edit', $board->id);
                                } catch (\Throwable $th) {
                                    throw $th;
                                }
                            } // Người dùng đã đăng nhập nhưng email khác
                            else {
                                Auth::logout();
                                Session::put('invited_board', "case4");
                                Session::put('board_id', $board->id);
                                Session::put('workspace_id', $board->workspace_id);
                                Session::put('user_id', $user->id);
                                Session::put('email_invited', $request->email);
                                Session::put('authorize', $request->authorize);
                                return redirect()->route('login');
                            }
                        } //xử lý khi người dùng có tài khoản rồi mà chưa đăng nhập đó
                        else {
                            Session::put('invited_board', "case4");
                            Session::put('board_id', $board->id);
                            Session::put('workspace_id', $board->workspace_id);
                            Session::put('user_id', $user->id);
                            Session::put('email_invited', $request->email);
                            Session::put('authorize', $request->authorize);
                            return redirect()->route('login');
                        }
                    }
                }
            } //xử lý khi người dùng không có tài khoản
            else {
                //xử lý khi người dùng không có tài khoản
                Auth::logout();
                Session::put('board_id', $board->id);
                Session::put('invited_board', 'case2');
                Session::put('workspace_id', $board->workspace_id);
                Session::put('email_invited', $request->email);
                Session::put('authorize', $request->authorize);
                return redirect()->route('register');
            }
        } //xử lý khi người dùng có link invite và kick vô
        else {
            $board = Board::where('link_invite', 'LIKE', "%$uuid/$token%")->first();
            if (!$board) {
                return redirect('/boardError');
            }
            Auth::logout();
            Session::put('board_id', $board->id);
            Session::put('workspace_id', $board->workspace_id);
            Session::put('board_access', $board->access);
            Session::put('authorize', AuthorizeEnum::Member());
            Session::put('invited_board', 'case3');
            return redirect()->route('login');
        }
    }

    //người dùng đang ở bảng mà chưa trong wsp thì bấm vào nút xin và wsp
//thông báo done
    public
        function requestToJoinWorkspace(
    ) {

        $workspace_member = WorkspaceMember::where('user_id', Auth::id())
            ->with('workspace')
            ->where('is_active', 1)
            ->first();

        $workspace_member->update([
            'is_accept_invite' => 1,
        ]);
        $workspaceMemberOwner = Workspace::where('id', $workspace_member->workspace_id)
            ->whereHas('workspaceMembers', function ($query) {
                $query->whereIn('authorize', ['Owner', 'Sub_Owner']);
            })
            ->with([
                'workspaceMembers' => function ($query) {
                    $query->whereIn('authorize', ['Owner', 'Sub_Owner']);
                },
                'workspaceMembers.user'
            ])
            ->first();
        $workspace = $workspaceMemberOwner;
        $userName = auth()->user()->name;
        $workspaceMemberOwner->workspaceMembers->each(function ($workspaceMember) use ($workspace, $userName) {
            $user = $workspaceMember->user; // Truy cập user từ WorkspaceMember
            $name = 'không gian làm việc ' . $workspace->name;
            $title = 'Lời mời vào không gian làm việc';
            $description = 'Người dùng "' . $userName . '" Đã gửi lời mời vào không gian làm việc!.';
            if ($user->id == Auth::id()) {
                event(new EventNotification($description, 'success', $user->id));
            }
            // Gửi notification cho user
            $user->notify(new WorkspaceNotification($user, $workspace, $name, $description, $title));
        });

        return response()->json([
            'success' => true,
            'msg' => "Bạn dẫ gửi yêu cầu tham gia vào không gian làm việc",
            'action' => 'success',
            'workspaceName' => $workspace_member->workspace->name
        ]);
    }

    //mời người dùng từ wsp vào bảng
    //thông báo done
    public
        function inviteMemberWorkspace(
        $userId,
        $boardId
    ) {
        if (session('view_only', false)) {
            return back()->with('error', 'Bạn chỉ có quyền xem và không thể chỉnh sửa bảng này.');
        }
        session()->forget('view_only');

        $check = BoardMember::where('user_id', Auth::id())->where('board_id', $boardId)->first();
        if ($check) {
            BoardMember::create([
                'user_id' => $userId,
                'board_id' => $boardId,
                'authorize' => "Member",
                'invite' => now(),
            ]);
        }
        $userName = User::find($userId)->name;
        $this->notificationMemberInviteBoard($boardId, $userName);

        return response()->json([
            'success' => true,
            'action' => 'success',
            'msg' => 'Bạn đã thêm người dùng vào bảng'
        ]);
    }

    //yêu cầu tham gia vào bảng
    public function requestToJoinboard($boardId)
    {
        $check = BoardMember::where('user_id', Auth::id())->where('board_id', $boardId)->first();
        if (!$check) {
            BoardMember::create([
                'user_id' => Auth::id(),
                'board_id' => $boardId,
                'authorize' => AuthorizeEnum::Member(),
                'invite' => now(),
                'is_accept_invite' => 1
            ]);

            $description = 'Người dùng "' . Auth::user()->name . '" đã gửi lời mời vào bảng!.';
            $this->notificationMemberJoinBoard($boardId, Auth::user()->name);

            $owner = BoardMember::where('board_id', $boardId)->where('authorize', 'Owner')->first();
            event(new EventNotification($description, 'success', $owner->user_id));
            return response()->json([
                'action' => 'success',
                'msg' => 'Bạn đã gửi yêu cầu tham gia vào bảng'
            ]);
        }

        return response()->json([
            'action' => 'success',
            'msg' => 'Bạn đã gửi yêu cầu tham gia rồi. Xin chờ duyệt'
        ]);
    }

    //thông báo người dùng tham gia vào bảng
    protected
        function notificationMemberInviteBoard(
        $boardID,
        $userName
    ) {
        // Eager load boardMembers và user, lọc authorize != Viewer
        $board = Board::with([
            'boardMembers' => function ($query) {
                $query->where('authorize', '!=', 'Viewer');
            },
            'boardMembers.user' // Eager load user
        ])->find($boardID);

        if ($board) {
            // Gửi thông báo tới các thành viên hợp lệ
            $board->boardMembers->each(function ($boardMember) use ($board, $userName) {
                $user = $boardMember->user;
                if ($user) {
                    $name = 'Bảng ' . $board->name;
                    $title = 'Thành viên mới trong bảng';
                    $description = 'Người dùng "' . $userName . '" đã được thêm vào bảng "' . $board->name . '".';

                    $user->notify(new BoardNotification($user, $board, $name, $description, $title));
                }
            });
        }
    }

    protected
        function notificationMemberJoinBoard(
        $boardID,
        $userName
    ) {
        // Eager load boardMembers và user, lọc authorize != Viewer
        $board = Board::with([
            'boardMembers' => function ($query) {
                $query->where('authorize', '!=', 'Viewer');
            },
            'boardMembers.user' // Eager load user
        ])->find($boardID);

        if ($board) {
            // Gửi thông báo tới các thành viên hợp lệ
            $board->boardMembers->each(function ($boardMember) use ($board, $userName) {
                $user = $boardMember->user;
                if ($user) {
                    $name = 'Bảng ' . $board->name;
                    $title = 'Yêu cầu tham gia vào bảng';
                    $description = 'Người dùng "' . $userName . '" đã gửi lời mời tham gia vào bảng "' . $board->name . '".';

                    $user->notify(new BoardNotification($user, $board, $name, $description, $title));
                }
            });
        }
    }

    //thông báo nhượng quyền
    protected
        function notificationManagementfranchiseBoard(
        $boardID,
        $userName
    ) {
        // Eager load boardMembers và user, lọc authorize != Viewer
        $board = Board::with([
            'boardMembers' => function ($query) {
                $query->where('authorize', '!=', 'Viewer');
            },
            'boardMembers.user' // Eager load user
        ])->find($boardID);

        if ($board) {
            // Gửi thông báo tới các thành viên hợp lệ
            $board->boardMembers->each(function ($boardMember) use ($board, $userName) {
                $user = $boardMember->user;
                if ($user) {
                    $name = 'Bảng ' . $board->name;
                    $title = 'Nhượng quyền';
                    $description = 'Người dùng "' . $userName . '" đã được nhượng quyền lên Chủ Nhóm.';
                    if ($user->id == Auth::id()) {
                        event(new EventNotification($description, 'success', $user->id));
                    }
                    $user->notify(new BoardNotification($user, $board, $name, $description, $title));
                }
            });
        }
    }

    //thông báo thăng cấp thành viên
    protected
        function notificationUpgradeMemberShipBoard(
        $boardID,
        $userName
    ) {
        // Eager load boardMembers và user, lọc authorize != Viewer
        $board = Board::with([
            'boardMembers' => function ($query) {
                $query->where('authorize', '!=', 'Viewer');
            },
            'boardMembers.user' // Eager load user
        ])->find($boardID);

        if ($board) {
            // Gửi thông báo tới các thành viên hợp lệ
            $board->boardMembers->each(function ($boardMember) use ($board, $userName) {
                $user = $boardMember->user;
                if ($user) {
                    $name = 'Bảng ' . $board->name;
                    $title = 'Thăng cấp thành viên';
                    $description = 'Người dùng "' . $userName . '" đã được thăng cấp lên Phó Nhóm.';
                    // if ($user->id == Auth::id()) {
                    //     event(new EventNotification($description, 'success', $user->id));
                    // }
                    $user->notify(new BoardNotification($user, $board, $name, $description, $title));
                }
            });
        }
    }

    //thông báo thăng cấp thành viên
    protected
        function notificationAcceptMemberBoard(
        $boardID,
        $userName
    ) {
        // Eager load boardMembers và user, lọc authorize != Viewer
        $board = Board::with([
            'boardMembers' => function ($query) {
                $query->where('authorize', '!=', 'Viewer');
            },
            'boardMembers.user' // Eager load user
        ])->find($boardID);

        if ($board) {
            // Gửi thông báo tới các thành viên hợp lệ
            $board->boardMembers->each(function ($boardMember) use ($board, $userName) {
                $user = $boardMember->user;
                if ($user) {
                    $name = 'Bảng ' . $board->name;
                    $title = 'Rời khỏi bảng';
                    $description = 'Người dùng "' . $userName . '" đã rời khỏi bảng.';
                    // if ($user->id == Auth::id()) {
                    //     event(new EventNotification($description, 'error', $user->id));
                    // }
                    $user->notify(new BoardNotification($user, $board, $name, $description, $title));
                }
            });
        }
    }
}
