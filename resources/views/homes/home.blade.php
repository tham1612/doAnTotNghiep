@extends('layouts.masterMain')
@section('title')
    Trang chủ - TaskFlow
@endsection
@section('main')
    @if (session('error'))
        <div class="alert alert-danger custom-alert">
            {{ session('error') }}
        </div>
    @endif

    <style>
        .custom-alert {
            border-radius: 0.5rem;
            padding: 1rem;
            position: relative;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>

    @php
        // Đếm tổng số thành viên của workspace
        $workspaceMembersCount = $workspaceMembers->count();

        // Nếu người dùng không phải là thành viên của workspace, kiểm tra thành viên của các board
        $boardMembersCount = $boards
            ->flatMap(function ($board) {
                return $board->boardMembers;
            })
            ->count();
    @endphp

    {{-- <div class="row" style="padding-top: -2px">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Trang chủ</h4>

            </div>
        </div>
    </div> --}}

    <div class="row row-cols-xxl-5 row-cols-lg-3 row-cols-sm-2 row-cols-1">
        <div class="col">
            <div class="card">
                <div class="card-body d-flex">
                    <div class="flex-grow-1">

                        @if ($workspaceMembersCount > 0)
                            <!-- Nếu workspace có thành viên -->
                            <h4>{{ $workspaceMembersCount }}</h4>
                            <h6 class="text-muted fs-13 mb-0">Thành viên </h6>
                        @elseif ($boardMembersCount > 0)
                            <!-- Nếu không có thành viên trong workspace nhưng có thành viên trong board -->
                            <h4>{{ $boardMembersCount }}</h4>
                            <h6 class="text-muted fs-13 mb-0">Thành viên </h6>
                        @else
                            <!-- Nếu không có thành viên nào -->
                            <h4>0</h4>
                            <h6 class="text-muted fs-13 mb-0">Thành viên</h6>
                        @endif
                    </div>

                    <div class="flex-shrink-0 avatar-sm">
                        <div class="avatar-title bg-warning-subtle text-warning fs-22 rounded">
                            <i class="ri-upload-2-line"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end col-->
        <div class="col">
            <div class="card">
                <div class="card-body d-flex">
                    <div class="flex-grow-1">
                        <h4>{{ $tasks->count() }}</h4>
                        <h6 class="text-muted fs-13 mb-0">Tổng Task</h6>
                    </div>
                    <div class="flex-shrink-0 avatar-sm">
                        <div class="avatar-title bg-success-subtle text-success fs-22 rounded">
                            <i class="ri-remote-control-line"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end col-->
        <div class="col">
            <div class="card">
                <div class="card-body d-flex">
                    <div class="flex-grow-1">
                        <h4>{{ $completedTasks->count() }}</h4>
                        <h6 class="text-muted fs-13 mb-0">Task hoàn thành</h6>
                    </div>
                    <div class="flex-shrink-0 avatar-sm">
                        <div class="avatar-title bg-info-subtle text-info fs-22 rounded">
                            <i class="ri-flashlight-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end col-->
        <div class="col">
            <div class="card">
                <div class="card-body d-flex">
                    <div class="flex-grow-1">
                        <h4>{{ $overdueTasks->count() }}</h4>
                        <h6 class="text-muted fs-13 mb-0">Task quá hạn</h6>
                    </div>
                    <div class="flex-shrink-0 avatar-sm">
                        <div class="avatar-title bg-danger-subtle text-danger fs-22 rounded">
                            <i class="ri-hand-coin-line"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end col-->
        <div class="col">
            <div class="card">
                <div class="card-body d-flex">
                    <div class="flex-grow-1">
                        <h4>{{ $incompleteTasks->count() }}</h4>
                        <h6 class="text-muted fs-13 mb-0">Chưa hoàn thành</h6>
                    </div>
                    <div class="flex-shrink-0 avatar-sm">
                        <div class="avatar-title bg-primary-subtle text-primary fs-22 rounded">
                            <i class="ri-donut-chart-line"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end col-->
    </div>
    <!--end row-->

    <div class="card">
        <div class="row">
            <div class="col-xl-4">
                <div class="card card-height-100">
                    <div class="card-header align-items-center d-flex">
                        <h4 class="card-title mb-0 flex-grow-1 fs-17">Công việc sắp tới</h4>
                    </div><!-- end card header -->
                    <div class="card-body p-0">
                        <div data-simplebar style="max-height: 260px;">
                            <ul class="list-group list-group-flush border-dashed px-3">
                                @if (!empty($upcomingTasks))
                                    @if ($upcomingTasks->isEmpty())
                                        <p>Không có task trong 1 tuần tới</p>
                                    @else
                                        @foreach ($upcomingTasks as $task)
                                            <li class="list-group-item ps-0">
                                                <div class="row align-items-center g-3">
                                                    <div class="col-auto">

                                                        <div class="flex-shrink-0 avatar-sm">
                                                            @if ($task && $task->image)
                                                                <img class="bg-info-subtle rounded d-flex justify-content-center align-items-center"
                                                                    src="{{ asset('storage/' . $task->image) }}"
                                                                    alt="" style="width: 50px;height: 50px;">
                                                            @else
                                                                <div class="avatar-title bg-light rounded">
                                                                    <div class="bg-info-subtle rounded d-flex justify-content-center align-items-center text-black"
                                                                        style="width: 50px;height: 50px;">
                                                                        {{ substr($task->text, 0, 1) }}
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="col">
                                                        @if ($task->start_date)
                                                            <h5 class="text-muted mt-0 mb-1 fs-13">
                                                                {{ \Carbon\Carbon::parse($task->start_date)->format('d/m/Y') }}
                                                                @if ($task->end_date)
                                                                    -
                                                                    {{ \Carbon\Carbon::parse($task->end_date)->format('d/m/Y') }}
                                                                @endif
                                                            </h5>
                                                        @endif
                                                        <h5>{{ \Illuminate\Support\Str::limit($task->text, 20) }}</h5>
                                                        {{-- <p>Danh sách: {{ $task->catalog_name }}</p> --}}
                                                        <a href="{{ route('b.edit', ['id' => $task->board_id]) }}">
                                                            <p>{{ \Illuminate\Support\Str::limit($task->catalog_name, 10) }}
                                                                -
                                                                {{ \Illuminate\Support\Str::limit($task->board_name, 20) }}
                                                            </p>
                                                        </a>
                                                    </div>
                                                    <div class="col-sm-auto">
                                                        <div class="avatar-group">
                                                            @if ($task->members->isNotEmpty())
                                                                @php
                                                                    // Giới hạn số thành viên hiển thị
                                                                    $maxDisplay = 2;
                                                                    $count = 0;
                                                                @endphp
                                                                @foreach ($task->members as $member)
                                                                    @if ($count < $maxDisplay)
                                                                        <a href=""
                                                                            class="avatar-group-item member-task"
                                                                            data-bs-toggle="tooltip" data-bs-trigger="hover"
                                                                            data-bs-placement="top"
                                                                            title="{{ $member->name }}">
                                                                            @if ($member->image)
                                                                                <img src="{{ asset('storage/' . $member->image) }}"
                                                                                    alt=""
                                                                                    class="rounded-circle avatar-xxs">
                                                                            @else
                                                                                <div
                                                                                    class="bg-info-subtle rounded-circle avatar-xxs d-flex justify-content-center align-items-center">
                                                                                    {{ strtoupper(substr($member->name, 0, 1)) }}
                                                                                </div>
                                                                            @endif
                                                                        </a>
                                                                        @php $count++; @endphp
                                                                    @endif
                                                                @endforeach

                                                                @if ($task->members->count() > $maxDisplay)
                                                                    <a href="javascript: void(0);" class="avatar-group-item"
                                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                                        title="{{ $task->members->count() - $maxDisplay }} more">
                                                                        <div class="avatar-xxs">
                                                                            <div
                                                                                class="avatar-title rounded-circle avatar-xxs bg-info-subtle d-flex justify-content-center align-items-center text-black">
                                                                                +{{ $task->members->count() - $maxDisplay }}
                                                                            </div>
                                                                        </div>
                                                                    </a>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </li><!-- end -->
                                        @endforeach
                                    @endif
                                @endif
                            </ul><!-- end ul -->
                        </div>
                    </div><!-- end card body -->

                </div><!-- end card -->
            </div><!-- end col -->
            <div class="col-xl-4">
                <div class="card card-height-100">
                    <div class="card-header align-items-center d-flex">
                        <h4 class="card-title mb-0 flex-grow-1 fs-17">Công việc được giao cho tôi</h4>
                    </div><!-- end card header -->
                    <div data-simplebar style="max-height: 260px;">
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush border-dashed px-3">
                                @if (!empty($myAssignedTasks))
                                    @if ($myAssignedTasks->isEmpty())
                                        <p>Công việc được giao cho tôi</p>
                                    @else
                                        @foreach ($myAssignedTasks as $task)
                                            <li class="list-group-item ps-0">
                                                <div class="row align-items-center g-3">
                                                    <div class="col-auto">

                                                        <div class="flex-shrink-0 avatar-sm">
                                                            @if ($task && $task->image)
                                                                <img class="bg-info-subtle rounded d-flex justify-content-center align-items-center"
                                                                    src="{{ asset('storage/' . $task->image) }}"
                                                                    alt="" style="width: 50px;height: 50px;">
                                                            @else
                                                                <div class="avatar-title bg-light rounded">
                                                                    <div class="bg-info-subtle rounded d-flex justify-content-center align-items-center text-black"
                                                                        style="width: 50px;height: 50px;">
                                                                        {{ substr($task->text, 0, 1) }}
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="col">
                                                        @if ($task->start_date && $task->end_date)
                                                            <h5 class="text-muted mt-0 mb-1 fs-13">
                                                                {{ \Carbon\Carbon::parse($task->start_date)->format('d/m/Y') }}
                                                                -
                                                                {{ \Carbon\Carbon::parse($task->end_date)->format('d/m/Y') }}
                                                            </h5>
                                                        @endif

                                                        <h5>{{ \Illuminate\Support\Str::limit($task->text, 20) }}</h5>
                                                        {{-- <p>Danh sách: {{ $task->catalog_name }}</p> --}}
                                                        <a href="{{ route('b.edit', ['id' => $task->board_id]) }}">
                                                            <p>{{ \Illuminate\Support\Str::limit($task->catalog_name, 10) }}
                                                                -
                                                                {{ \Illuminate\Support\Str::limit($task->board_name, 10) }}
                                                            </p>
                                                        </a>
                                                    </div>
                                                </div>
                                            </li><!-- end -->
                                        @endforeach
                                    @endif
                                @endif
                            </ul>
                        </div><!-- end card body -->
                    </div>
                </div><!-- end card -->
            </div><!-- end col -->
            <div class="col-xl-4">
                <div class="card card-height-100">
                    <div class="card-header border-bottom-dashed align-items-center d-flex">
                        <h4 class="card-title mb-0 flex-grow-1 fs-17">Hoạt động gần đây</h4>
                        <div class="flex-shrink-0">

                        </div>
                    </div><!-- end cardheader -->
                    <div class="card-body p-0">
                        <div data-simplebar style="max-height: 260px;" class="p-3">
                            <div class="acitivity-timeline acitivity-main">
                                @if (!empty($activities))
                                    @foreach ($activities as $activity)
                                        <li class="d-flex align-items-start mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    @if (!empty($activity->causer) && !empty($activity->causer->avatar))
                                                        <img src="{{ asset('path_to_avatar/' . $activity->causer->avatar) }}"
                                                            alt="avatar" class="rounded-circle" width="40"
                                                            height="40">
                                                    @else
                                                        <div class="bg-info-subtle rounded-circle d-flex justify-content-center align-items-center"
                                                            style="width: 40px; height: 40px;">
                                                            {{ strtoupper(substr($activity->causer->name ?? 'Hệ thống', 0, 1)) }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div>
                                                <p class="mb-1">
                                                    <strong>{{ $activity->causer->name ?? 'Hệ thống' }}:</strong>
                                                    {{ $activity->description ?? 'Không có mô tả' }}
                                                </p>
                                                <small class="text-muted">
                                                    {{ $activity && $activity->created_at ? $activity->created_at->locale('vi')->diffForHumans() : 'Không xác định thời gian' }}
                                                </small>
                                            </div>
                                        </li>
                                    @endforeach
                                @endif

                            </div>
                        </div>
                    </div><!-- end card body -->
                </div><!-- end card -->
            </div><!-- end col -->
        </div> <!-- end row-->
        <div class="row mb-3 me-2 ms-2">
            <div class="col-xxl-3 col-md-6">
                <div class="card overflow-hidden">
                    <div class="card-body bg-success-subtle">
                        <h5 class="fs-17 text-center mb-0">Task gần đến hạn</h5>
                    </div>
                </div>
                <div data-simplebar style="max-height: 450px;">
                    @if (!empty($tasksExpiringSoon))
                        @if ($tasksExpiringSoon->isEmpty())
                            <p>Không có Task gần đến hạn</p>
                        @else
                            @foreach ($tasksExpiringSoon as $task)
                                <div class="card mb-2">
                                    <div class="card-body">
                                        <div class="d-flex mb-3">
                                            <div class="flex-shrink-0 avatar-sm">
                                                @if ($task && $task->image)
                                                    <img class="bg-info-subtle rounded d-flex justify-content-center align-items-center"
                                                        src="{{ asset('storage/' . $task->image) }}" alt=""
                                                        style="width: 50px;height: 50px;">
                                                @else
                                                    <div class="avatar-title bg-light rounded">
                                                        <div class="bg-info-subtle rounded d-flex justify-content-center align-items-center text-black"
                                                            style="width: 50px;height: 50px;">
                                                            {{ substr($task->text, 0, 1) }}
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h5>{{ \Illuminate\Support\Str::limit($task->text, 20) }}</h5>
                                                <a href="{{ route('b.edit', ['id' => $task->board_id]) }}">
                                                    <p><span>{{ Str::limit($task->catalog_name, 10) }}</span> -
                                                        <span>{{ Str::limit($task->board_name, 10) }}</span>
                                                    </p>
                                                </a>
                                                <h5 class="text-muted mt-0 mb-1 fs-13">
                                                    {{ \Carbon\Carbon::parse($task->start_date)->format('d/m/Y') }} -
                                                    {{ \Carbon\Carbon::parse($task->end_date)->format('d/m/Y') }}</h5>
                                            </div>
                                        </div>
                                        <div class="d-flex" style="justify-content: space-between;">
                                            <h6 class="text-muted mb-0">Progress: {{ $task->progress }}%</h6>
                                            <div>
                                                <a href="{{ route('b.edit', ['id' => $task->board_id]) }}"
                                                    class="badge bg-primary-subtle text-primary">
                                                    Xem chi tiết
                                                    <i class="ri-arrow-right-up-line align-bottom"></i></a>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            @endforeach
                        @endif
                    @endif
                </div>
            </div>
            <div class="col-xxl-3 col-md-6">
                <div class="card overflow-hidden">
                    <div class="card-body bg-danger-subtle">
                        <h5 class="fs-17 text-center mb-0">Task quá hạn</h5>
                    </div>
                </div>
                <div data-simplebar style="max-height: 450px;">
                    @if (!empty($overdueTasks))
                        @if ($overdueTasks->isEmpty())
                            <p>Không có Task quá hạn</p>
                        @else
                            @foreach ($overdueTasks as $task)
                                <div class="card mb-2">
                                    <div class="card-body">
                                        <div class="d-flex mb-3">
                                            <div class="flex-shrink-0 avatar-sm">
                                                @if ($task && $task->image)
                                                    <img class="bg-info-subtle rounded d-flex justify-content-center align-items-center"
                                                        src="{{ asset('storage/' . $task->image) }}" alt=""
                                                        style="width: 50px;height: 50px;">
                                                @else
                                                    <div class="avatar-title bg-light rounded">
                                                        <div class="bg-info-subtle rounded d-flex justify-content-center align-items-center text-black"
                                                            style="width: 50px;height: 50px;">
                                                            {{ substr($task->text, 0, 1) }}
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h5>{{ \Illuminate\Support\Str::limit($task->text, 20) }}</h5>
                                                <a href="{{ route('b.edit', ['id' => $task->board_id]) }}">
                                                    <p><span>{{ Str::limit($task->catalog_name, 10) }}</span> -
                                                        <span>{{ Str::limit($task->board_name, 10) }}</span>
                                                    </p>

                                                </a>
                                                <h5 class="text-muted mt-0 mb-1 fs-13">
                                                    {{ \Carbon\Carbon::parse($task->start_date)->format('d/m/Y') }} -
                                                    {{ \Carbon\Carbon::parse($task->end_date)->format('d/m/Y') }}</h5>
                                            </div>
                                        </div>
                                        <div class="d-flex" style="justify-content: space-between;">
                                            <h6 class="text-muted mb-0">Progress: {{ $task->progress }}%</h6>
                                            <div>
                                                <a href="{{ route('b.edit', ['id' => $task->board_id]) }}"
                                                    class="badge bg-primary-subtle text-primary">
                                                    Xem chi tiết
                                                    <i class="ri-arrow-right-up-line align-bottom"></i></a>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            @endforeach
                        @endif
                    @endif
                </div>
            </div>
            <div class="col-xxl-3 col-md-6">
                <div class="card overflow-hidden">
                    <div class="card-body bg-primary-subtle">
                        <h5 class="fs-17 text-center mb-0">Bảng nổi bật</h5>
                    </div>
                </div>
                <div data-simplebar style="max-height: 450px;">
                    @if (!empty($board_star))
                        @if ($board_star->isEmpty())
                            <p>Không có bảng nào được đánh dấu là nổi bật</p>
                        @else
                            @foreach ($board_star as $board)
                                <div class="card mb-2">
                                    <div class="card-body">
                                        <div class="d-flex mb-3">
                                            <div class="flex-shrink-0 avatar-sm">

                                                @if ($board && $board->image)
                                                    <img class="bg-info-subtle rounded d-flex justify-content-center align-items-center"
                                                        src="{{ asset('storage/' . $board->image) }}" alt=""
                                                        style="width: 50px;height: 50px;">
                                                @else
                                                    <div class="avatar-title bg-light rounded">
                                                        <div class="bg-info-subtle rounded d-flex justify-content-center align-items-center text-black"
                                                            style="width: 50px;height: 50px;">
                                                            {{ strtoupper(substr($board->name, 0, 1)) }}
                                                        </div>
                                                    </div>
                                                @endif

                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h5 class="fs-16 mb-1"><a
                                                        href="{{ route('b.edit', ['viewType' => 'dashboard', 'id' => $board->id]) }}"
                                                        class="text-body">{{ \Illuminate\Support\Str::limit($board->name, 20) }}</a>
                                                </h5>
                                                <h5 class="text-muted mt-0 mb-1 fs-13">
                                                    {{ \Carbon\Carbon::parse($board->created_at)->format('d/m/Y') }}
                                                </h5>
                                            </div>
                                            <button type="button"
                                                class="btn avatar-xs p-0 favourite-btn {{ $board->is_star ? 'active' : '' }}"
                                                onclick="updateIsStar2({{ $board->id }}, {{ auth()->id() }})"
                                                id="is_star_{{ $board->id }}">
                                                <span class="avatar-title bg-transparent fs-15">
                                                    <i class="ri-star-fill"></i>
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body border-top border-top-dashed">
                                        <div class="d-flex" style="justify-content: space-between;">
                                            <div class="d-flex mb-2">
                                                <div class="flex-grow-1">
                                                    <div>Tiến độ: </div>
                                                </div>
                                                <div class="ms-1">
                                                    {{ $board->complete }}% / 100%
                                                    <!-- Hiển thị phần trăm hoàn thành -->
                                                </div>
                                            </div>

                                            <div>
                                                <a class="nav-link badge bg-primary-subtle text-primary {{ request()->get('type') == 'dashboard' ? 'active' : '' }}"
                                                    href="{{ route('b.edit', ['viewType' => 'dashboard', 'id' => $board->id]) }}"
                                                    role="tab" aria-controls="pills-home"
                                                    aria-selected="{{ request()->get('type') == 'dashboard' ? 'true' : 'false' }}">
                                                    <i class="ri-arrow-right-up-line align-bottom"></i> Xem chi tiết
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    @endif

                </div>
                <!--end card-->
            </div>
            <div class="col-xxl-3 col-md-6">
                <div class="card overflow-hidden">
                    <div class="card-body bg-info-subtle">
                        <h5 class="fs-17 text-center mb-0">Các bảng được tạo bởi tôi</h5>
                    </div>
                </div>
                <div data-simplebar style="max-height: 450px;">
                    @if (!empty($ownerBoards))
                        @if ($ownerBoards->isEmpty())
                            <p>Không có bảng nào</p>
                        @else
                            @foreach ($ownerBoards as $board)
                                <div class="card mb-2">
                                    <div class="card-body">
                                        <div class="d-flex mb-3">
                                            <div class="flex-shrink-0 avatar-sm">
                                                @if ($board && $board->image)
                                                    <img class="bg-info-subtle rounded d-flex justify-content-center align-items-center"
                                                        src="{{ asset('storage/' . $board->image) }}" alt=""
                                                        style="width: 50px;height: 50px;">
                                                @else
                                                    <div class="avatar-title bg-light rounded">
                                                        <div class="bg-info-subtle rounded d-flex justify-content-center align-items-center text-black"
                                                            style="width: 50px;height: 50px;">
                                                            {{ strtoupper(substr($board->name, 0, 1)) }}
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h5 class="fs-16 mb-1"><a
                                                        href="{{ route('b.edit', ['viewType' => 'dashboard', 'id' => $board->id]) }}"
                                                        class="text-body">{{ \Illuminate\Support\Str::limit($board->name, 30) }}</a>
                                                </h5>
                                                <h5 class="text-muted mt-0 mb-1 fs-13">
                                                    {{ \Carbon\Carbon::parse($board->created_at)->format('d/m/Y') }}
                                                </h5>
                                            </div>
                                            <button type="button"
                                            class="btn avatar-xs p-0 favourite-btn {{ $board->is_star ? 'active' : '' }}"
                                            onclick="updateIsStar2({{ $board->id }}, {{ auth()->id() }})"
                                            id="is_star_{{ $board->id }}">
                                            <span class="avatar-title bg-transparent fs-15">
                                                <i class="ri-star-fill"></i>
                                            </span>
                                        </button>
                                        </div>
                                    </div>

                                    <div class="card-body border-top border-top-dashed">
                                        <div class="d-flex" style="justify-content: space-between;">
                                            <div class="d-flex mb-2">
                                                <div class="flex-grow-1">
                                                    <div>Tiến độ: </div>
                                                </div>
                                                <div class="ms-1">
                                                    {{ $board->complete }}% / 100%
                                                    <!-- Hiển thị phần trăm hoàn thành -->
                                                </div>
                                            </div>

                                            <div>
                                                <a class="nav-link badge bg-primary-subtle text-primary {{ request()->get('type') == 'dashboard' ? 'active' : '' }}"
                                                    href="{{ route('b.edit', ['viewType' => 'dashboard', 'id' => $board->id]) }}"
                                                    role="tab" aria-controls="pills-home"
                                                    aria-selected="{{ request()->get('type') == 'dashboard' ? 'true' : 'false' }}">
                                                    <i class="ri-arrow-right-up-line align-bottom"></i> Xem chi tiết
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    @endif
                </div>
                <!--end card-->
            </div>
        </div>
    </div>
    <!--end row-->

@endsection

@section('script')
    <!-- apexcharts -->
    <script src="{{ asset('theme/assets/libs/apexcharts/apexcharts.min.js') }}"></script>

    <!-- Vector map-->
    <script src="{{ asset('theme/assets/libs/jsvectormap/js/jsvectormap.min.js') }}"></script>
    <script src="{{ asset('theme/assets/libs/jsvectormap/maps/world-merc.js') }}"></script>

    <!--Swiper slider js-->
    <script src="{{ asset('theme/assets/libs/swiper/swiper-bundle.min.js') }}"></script>

    <!-- Dashboard init -->
    <script src="{{ asset('theme/assets/js/pages/dashboard-ecommerce.init.js') }}"></script>
    <!-- project list init -->
    <script src="{{ asset('theme/assets/js/pages/project-list.init.js') }}"></script>
@endsection

@section('styles')
    <!-- jsvectormap css -->
    <link href="{{ asset('theme/assets/libs/jsvectormap/css/jsvectormap.min.css') }}" rel="stylesheet"
        type="text/css" />

    <!--Swiper slider css-->
    <link href="{{ asset('theme/assets/libs/swiper/swiper-bundle.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
