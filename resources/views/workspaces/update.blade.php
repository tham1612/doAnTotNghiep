@extends('layouts.masterMain')
@section('main')

    <div class="row justify-content-center">
        <div class="col-xxl-9">
            <div class="card">
                <div class="card-body border-bottom border-bottom-dashed p-4">
                    <div class="row">

                        <div class="col-lg-6">
                            <form id="editWorkspaceForm" action="{{ route('editWorkspace') }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="profile-user mx-auto mb-3">
                                    <label for="profile-img-file-input" class="d-block" tabindex="0">
                                        <span
                                            class="overflow-hidden border border-dashed d-flex align-items-center justify-content-center rounded"
                                            style="height: 60px; width: 256px;">
                                            <img src="{{ asset('theme/assets/images/logo-dark.png') }}"
                                                class="card-logo card-logo-dark user-profile-image img-fluid"
                                                alt="logo dark">
                                            <img src="{{ asset('theme/assets/images/logo-light.png') }}"
                                                class="card-logo card-logo-light user-profile-image img-fluid"
                                                alt="logo light">
                                        </span>
                                    </label>

                                    <div class="d-flex align-items-center">
                                        <div class="profile-user position-relative d-inline-block mx-auto mb-4">
                                            <input type="hidden" value="{{ $workspaceChecked->workspace_id }}"
                                                name="workspace_id">

                                            @if ($workspaceChecked->image)
                                                <img class="rounded avatar-xl img-thumbnail user-profile-imager"
                                                    src="{{ \Illuminate\Support\Facades\Storage::url($workspaceChecked->image) }}"
                                                    alt="Avatar" />
                                            @else
                                                <div class="bg-info-subtle rounded d-flex justify-content-center align-items-center fs-20"
                                                    style="width: 80px;height: 80px">
                                                    {{ strtoupper(substr($workspaceChecked->name, 0, 1)) }}
                                                </div>
                                            @endif
                                            <div class="avatar-xs p-0 rounded-circle profile-photo-edit">
                                                <input type="file" name="image"
                                                    class="profile-img-file-input @error('image') is-invalid @enderror"
                                                    id="image" placeholder="Image">
                                                @error('image')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <label for="image" class="profile-photo-edit avatar-xs">
                                                    <span class="avatar-title rounded-circle bg-light text-body">
                                                        <i class="ri-camera-fill"></i>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="ms-3">
                                            <h5 class="m-0">{{ $workspaceChecked->wsp_name }}</h5>
                                            <span class="text-muted small"><i
                                                    class="bi bi-globe"></i>{{ $access }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <div><label for="name">Tên không gian làm việc</label></div>
                                    <input type="text" name="name"
                                        class="form-control bg-light @error('name') is-invalid @enderror" id="name"
                                        value="{{ old('name', $workspaceChecked->name) }}" />
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label for="description">Mô tả</label>
                                </div>
                                <div class="mb-2">
                                    <textarea name="description" class="form-control bg-light" id="description" rows="3" placeholder="Mô tả">{{ $workspaceChecked->description }}</textarea>
                                </div>

                                <button type="submit" class="btn btn-primary mt-2">Lưu</button>
                            </form>

                            <div id="formResponse" class="mt-3"></div>

                        </div>

                        <!--end col-->
                        <div class="col-lg-6 ms-auto">
                            <div class="mt-5">
                                <div class="bg-primary p-2 rounded text-center">
                                    <i class="ri-user-add-line text-white"></i>
                                    <a href="#addmemberModal" data-bs-toggle="modal" class="avatar-group-item">
                                        <span class="text-white">Mời thành viên vào Không gian làm việc</span>
                                    </a>
                                </div>
                                {{-- @include('components.invitemember') --}}

                                <div class="modal fade" id="addmemberModal" tabindex="-1"
                                    aria-labelledby="addmemberModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content border-0" style="width: 125%;">
                                            <div class="modal-header p-3">
                                                <h5 class="modal-title" id="addmemberModalLabel">
                                                    Chia sẻ không gian làm việc
                                                </h5>
                                                <button type="button" class="btn-close" id="btn-close-member"
                                                    data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">

                                                <div class="row g-3">
                                                    {{-- <form onsubmit="disableButtonOnSubmit()"
                                                        action="{{ route('invite_workspace', $workspaceChecked->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        <div class=" d-flex justify-content-between">
                                                            <div class="col-6">
                                                                <input type="email" class="form-control"
                                                                    id="submissionidInput"
                                                                    placeholder="Nhập email hoặc tên người dùng"
                                                                    name="email" />
                                                            </div>
                                                            <div class="col-4 ms-2">
                                                                <select name="authorize" id=""
                                                                    class="form-select">
                                                                    <option value="Member">Thành Viên</option>
                                                                    @if ($workspaceChecked->authorize !== 'Member' && $workspaceChecked->authorize !== 'Viewer')
                                                                        <option value="Sub_Owner">Phó nhóm</option>
                                                                    @endif
                                                                </select>
                                                            </div>
                                                            <div class="col-2 d-flex justify-content-center">
                                                                <button type="submit" class="btn btn-primary">
                                                                    Chia sẻ
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form> --}}
                                                    <form id="inviteForm">
                                                        @csrf
                                                        <div class="d-flex justify-content-between">
                                                            <div class="col-6">
                                                                <input type="email" class="form-control"
                                                                    id="submissionidInput"
                                                                    placeholder="Nhập email hoặc tên người dùng"
                                                                    name="email" />
                                                            </div>
                                                            <div class="col-4 ms-2">
                                                                <select name="authorize" id="authorizeInput"
                                                                    class="form-select">
                                                                    <option value="Member">Thành Viên</option>
                                                                    @if ($workspaceChecked->authorize !== 'Member' && $workspaceChecked->authorize !== 'Viewer')
                                                                        <option value="Sub_Owner">Phó nhóm</option>
                                                                    @endif
                                                                </select>
                                                            </div>
                                                            <div class="col-2 d-flex justify-content-center">
                                                                <button type="button" id="inviteButton"
                                                                    class="btn btn-primary">Chia sẻ</button>
                                                            </div>
                                                        </div>
                                                    </form>

                                                    <!--end col-->
                                                    <div class="d-flex justify-content-between">
                                                        <div class="col-1">
                                                            <a href="#">
                                                                <i id="copy-icon" class="ri-attachment-2 fs-22"
                                                                    onclick="copyLink()"></i>
                                                            </a>
                                                        </div>
                                                        <div class="col-6 d-flex flex-column">
                                                            <section class="fs-12">
                                                                <p style="margin-bottom: -5px;">Bất kỳ ai có thể theo
                                                                    gia
                                                                    với tư cách thành viên</p>
                                                                <span><a href="#" onclick="copyLink()">Sao chép liên
                                                                        kết</a></span>
                                                            </section>
                                                        </div>
                                                        <div class="col-5">
                                                        </div>
                                                    </div>
                                                    <!--end col-->
                                                    <ul class="nav nav-tabs nav-tabs-custom nav-success nav-justified mb-3"
                                                        role="tablist">
                                                        <li
                                                            class="nav-item d-flex align-items-center justify-content-between">
                                                            <a class="nav-link active" data-bs-toggle="tab"
                                                                href="#home1" role="tab">
                                                                Thành viên
                                                            </a>
                                                            <span
                                                                class="badge bg-dark align-items-center justify-content-center d-flex"
                                                                style="border-radius: 100%; width: 20px ;height: 20px;"
                                                                id='tab_1'>{{ $wspMemberCount + $wspSubOwnerCount + 1 }}</span>
                                                        </li>
                                                        @if ($workspaceChecked->authorize == 'Owner' || $workspaceChecked->authorize == 'Sub_Owner')
                                                            <li
                                                                class="nav-item d-flex align-items-center justify-content-between">
                                                                <a class="nav-link" data-bs-toggle="tab" href="#profile1"
                                                                    role="tab">
                                                                    Yêu cầu tham gia
                                                                </a>
                                                                <span
                                                                    class="badge bg-dark align-items-center justify-content-center d-flex"
                                                                    style="border-radius: 100%; width: 20px ;height: 20px;"
                                                                    id='tab_2'>{{ $wspInviteCount }}</span>
                                                            </li>
                                                        @endif
                                                        <li
                                                            class="nav-item d-flex align-items-center justify-content-between">
                                                            <a class="nav-link" data-bs-toggle="tab" href="#profile2"
                                                                role="tab">
                                                                Người xem
                                                            </a>
                                                            <span
                                                                class="badge bg-dark align-items-center justify-content-center d-flex"
                                                                id='tab_3'
                                                                style="border-radius: 100%; width: 20px ;height: 20px;">{{ $wspViewerCount }}</span>
                                                        </li>

                                                    </ul>
                                                    <!-- Tab panes -->
                                                    <div class="tab-content text-muted">
                                                        <div class="tab-pane active" id="home1" role="tabpanel">
                                                            {{-- <div class="scrollable-content"
                                                                style="max-height: 400px; overflow-y: auto;"> --}}
                                                            <ul style="margin-left: -32px;" id="tab-ul-1">
                                                                <li class="d-flex justify-content-between">
                                                                    <div class="d-flex">
                                                                        <a href="javascript: void(0);"
                                                                            class="avatar-group-item"
                                                                            data-bs-toggle="tooltip"
                                                                            data-bs-trigger="hover"
                                                                            data-bs-placement="top" title="Nancy">
                                                                            @if (!empty($wspOwner))
                                                                                @if ($wspOwner->image)
                                                                                    <img src="{{ Storage::url($wspOwner->image) ? Storage::url($wspOwner->image) : '' }}"
                                                                                        alt=""
                                                                                        class="rounded-circle avatar-xs" />
                                                                                @else
                                                                                    <div class="bg-info-subtle rounded d-flex justify-content-center align-items-center"
                                                                                        style="width: 25px;height: 25px">
                                                                                        {{ strtoupper(substr($wspOwner->name, 0, 1)) }}
                                                                                    </div>
                                                                                    {{-- <span class="fs-15 ms-2 text-white"
                                                                                        id="swicthWs">
                                                                                        {{ \Illuminate\Support\Str::limit($wspOwner->name, 16) }}
                                                                                        <i
                                                                                            class=" ri-arrow-drop-down-line fs-20"></i>
                                                                                    </span> --}}
                                                                                @endif
                                                                            @endif
                                                                        </a>
                                                                        <div class="ms-3 d-flex flex-column">
                                                                            @if (!empty($wspOwner))
                                                                                <section class="fs-12">
                                                                                    <p style="margin-bottom: 0px;"
                                                                                        class="text-danger fw-bloder">
                                                                                        {{ $wspOwner->name }}
                                                                                        @if ($wspOwner->user_id == $userId)
                                                                                            <span
                                                                                                class="text-danger fw-bloder">(bạn)</span>
                                                                                        @else
                                                                                            <span
                                                                                                class="text-danger fw-bold">(chủ)</span>
                                                                                        @endif
                                                                                    </p>
                                                                                    <span>
                                                                                        {{ $wspOwner->fullName ? '@' . $wspOwner->fullName : '@' . $wspOwner->name }}
                                                                                    </span>
                                                                                    <span>-</span>
                                                                                    <span>Quản trị viên không gian làm
                                                                                        việc</span>
                                                                                </section>
                                                                            @endif
                                                                        </div>
                                                                    </div>

                                                                    <div
                                                                        class=" d-flex align-items-center justify-content-end">
                                                                        <button class="btn btn-outline-danger">Quản trị
                                                                            viên
                                                                        </button>
                                                                        <!-- Nút ba chấm -->

                                                                        <div class="dropdown ms-2">

                                                                            <i class="ri-more-2-fill cursor-pointer"
                                                                                id="dropdownMenuButton"
                                                                                data-bs-toggle="dropdown"
                                                                                aria-expanded="false"></i>
                                                                            @if (!empty($wspOwner))
                                                                                @if ($wspOwner->user_id == $userId)
                                                                                    <!-- Popup xuất hiện khi nhấn nút ba chấm -->
                                                                                    <ul class="dropdown-menu"
                                                                                        aria-labelledby="dropdownMenuButton">
                                                                                        <li>
                                                                                            <a class="dropdown-item text-danger"
                                                                                                href="{{ route('leaveWorkspace', $wspOwner->wm_id) }}">Rời
                                                                                                khỏi</a>
                                                                                        </li>
                                                                                    </ul>
                                                                                @endif
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </li>
                                                                {{-- Lặp lại các sub owner --}}
                                                                @foreach ($wspSubOwner as $item)
                                                                    <li class="d-flex justify-content-between mt-2"
                                                                        id="li_{{ $item->wm_id }}">
                                                                        <div class="d-flex">
                                                                            <a href="javascript: void(0);"
                                                                                class="avatar-group-item"
                                                                                data-bs-toggle="tooltip"
                                                                                data-bs-trigger="hover" data-bs-item="top"
                                                                                title="Nancy">
                                                                                @if ($item->image)
                                                                                    <img src="{{ Storage::url($item->image) ? Storage::url($item->image) : '' }}"
                                                                                        alt=""
                                                                                        class="rounded-circle avatar-xs" />
                                                                                @else
                                                                                    <div class="bg-info-subtle rounded d-flex justify-content-center align-items-center"
                                                                                        style="width: 25px;height: 25px">
                                                                                        {{ strtoupper(substr($item->name, 0, 1)) }}
                                                                                    </div>
                                                                                    {{-- <span class="fs-15 ms-2 text-white"
                                                                                        id="swicthWs">
                                                                                        {{ \Illuminate\Support\Str::limit($item->name, 16) }}
                                                                                        <i
                                                                                            class=" ri-arrow-drop-down-line fs-20"></i>
                                                                                    </span> --}}
                                                                                @endif
                                                                            </a>
                                                                            <div class="ms-3 d-flex flex-column">
                                                                                <section class="fs-12">
                                                                                    <p style="margin-bottom: 0px;"
                                                                                        class="text-black">
                                                                                        {{ $item->name }}
                                                                                        @if ($item->user_id == $userId)
                                                                                            <span
                                                                                                class="text-success">(Bạn)</span>
                                                                                        @else
                                                                                            <span class="text-success">(Phó
                                                                                                nhóm)</span>
                                                                                        @endif
                                                                                    </p>
                                                                                    <span>
                                                                                        {{ $item->fullName ? '@' . $item->fullName : '@' . $item->name }}
                                                                                    </span>
                                                                                    <span>-</span>
                                                                                    <span>Phó nhóm của không gian làm
                                                                                        việc</span>
                                                                                </section>
                                                                            </div>
                                                                        </div>

                                                                        <div
                                                                            class=" d-flex align-items-center justify-content-end">
                                                                            <button
                                                                                class="btn btn-outline-success activate-member"
                                                                                data-wm-id="{{ $item->wm_id }}">Phó
                                                                                nhóm
                                                                            </button>
                                                                            <!-- Nút ba chấm -->
                                                                            <div class="dropdown ms-2">

                                                                                <i class="ri-more-2-fill cursor-pointer"
                                                                                    id="dropdownMenuButton"
                                                                                    data-bs-toggle="dropdown"
                                                                                    aria-expanded="false"></i>
                                                                                @if ($item->user_id === $userId)
                                                                                    <ul class="dropdown-menu"
                                                                                        aria-labelledby="dropdownMenuButton">
                                                                                        <li>
                                                                                            <a class="dropdown-item text-danger"
                                                                                                href="{{ route('leaveWorkspace', $item->wm_id) }}">Rời
                                                                                                khỏi</a>
                                                                                        </li>
                                                                                    </ul>
                                                                                @elseif($workspaceChecked->authorize == 'Owner')
                                                                                    <ul class="dropdown-menu"
                                                                                        aria-labelledby="dropdownMenuButton">
                                                                                        <li>
                                                                                            <a class="dropdown-item text-danger"
                                                                                                href="{{ route('activateMember', $item->wm_id) }}">Kích
                                                                                                phó
                                                                                                nhóm</a>
                                                                                        </li>
                                                                                        <li>
                                                                                            <a class="dropdown-item text-primary"
                                                                                                href="{{ route('managementfranchise', ['owner_id' => $wspOwner, 'user_id' => $item->id]) }}">Nhượng
                                                                                                quyền</a>
                                                                                        </li>
                                                                                    </ul>
                                                                                @endif
                                                                                <!-- Popup xuất hiện khi nhấn nút ba chấm -->
                                                                            </div>
                                                                        </div>
                                                                    </li>
                                                                @endforeach
                                                                <!-- Lặp lại với các thành viên -->
                                                                @foreach ($wspMember as $item)
                                                                    <li class="d-flex justify-content-between mt-2"
                                                                        id="li_{{ $item->wm_id }}">
                                                                        <div class="d-flex">
                                                                            <a href="javascript: void(0);"
                                                                                class="avatar-group-item"
                                                                                data-bs-toggle="tooltip"
                                                                                data-bs-trigger="hover" data-bs-item="top"
                                                                                title="Nancy">
                                                                                @if ($item->image)
                                                                                    <img src="{{ Storage::url($item->image) ? Storage::url($item->image) : '' }}"
                                                                                        alt=""
                                                                                        class="rounded-circle avatar-xs" />
                                                                                @else
                                                                                    <div class="bg-info-subtle rounded d-flex justify-content-center align-items-center"
                                                                                        style="width: 25px;height: 25px">
                                                                                        {{ strtoupper(substr($item->name, 0, 1)) }}
                                                                                    </div>
                                                                                    {{-- <span class="fs-15 ms-2 text-white"
                                                                                        id="swicthWs">
                                                                                        {{ \Illuminate\Support\Str::limit($item->name, 16) }}
                                                                                        <i
                                                                                            class=" ri-arrow-drop-down-line fs-20"></i>
                                                                                    </span> --}}
                                                                                @endif
                                                                            </a>
                                                                            <div class="ms-3 d-flex flex-column">
                                                                                <section class="fs-12">
                                                                                    <p style="margin-bottom: 0px;"
                                                                                        class="text-black">
                                                                                        {{ $item->name }}
                                                                                        @if ($item->user_id == $userId)
                                                                                            <span
                                                                                                class="text-success">(Bạn)</span>
                                                                                        @elseif($item->authorize === 'Sub_Owner')
                                                                                            <span class="text-primary">(Phó
                                                                                                nhóm)</span>
                                                                                        @else
                                                                                            <span class="text-black">(Thành
                                                                                                viên)</span>
                                                                                        @endif
                                                                                    </p>
                                                                                    <span>
                                                                                        {{ $item->fullName ? '@' . $item->fullName : '@' . $item->name }}
                                                                                    </span>
                                                                                    <span>-</span>
                                                                                    <span>Thành viên của không gian làm
                                                                                        việc</span>

                                                                                </section>
                                                                            </div>
                                                                        </div>

                                                                        <div
                                                                            class=" d-flex align-items-center justify-content-end">
                                                                            <button
                                                                                class="btn btn-outline-primary activate-member"
                                                                                data-wm-id="{{ $item->wm_id }}">
                                                                                Thành
                                                                                viên
                                                                            </button>
                                                                            <!-- Nút ba chấm -->
                                                                            <div class="dropdown ms-2">
                                                                                <i class="ri-more-2-fill cursor-pointer"
                                                                                    id="dropdownMenuButton"
                                                                                    data-bs-toggle="dropdown"
                                                                                    aria-expanded="false"></i>
                                                                                <!-- Popup xuất hiện khi nhấn nút ba chấm -->
                                                                                @if ($item->user_id === $userId)
                                                                                    <ul class="dropdown-menu"
                                                                                        aria-labelledby="dropdownMenuButton">
                                                                                        <li>
                                                                                            <a class="dropdown-item text-danger"
                                                                                                href="{{ route('leaveWorkspace', $item->wm_id) }}">Rời
                                                                                                khỏi</a>
                                                                                        </li>
                                                                                    </ul>
                                                                                @elseif($workspaceChecked->authorize == 'Owner')
                                                                                    <ul class="dropdown-menu"
                                                                                        aria-labelledby="dropdownMenuButton">
                                                                                        <li>
                                                                                            <a class="dropdown-item text-primary"
                                                                                                href="{{ route('managementfranchise', ['owner_id' => $wspOwner, 'user_id' => $item->id]) }}">Nhượng
                                                                                                quyền</a>
                                                                                        </li>
                                                                                        <li>
                                                                                            {{-- <a class="dropdown-item text-primary"
                                                                                                href="{{ route('upgradeMemberShip', $item->wm_id) }}">Thăng
                                                                                                cấp
                                                                                                thành
                                                                                                viên</a> --}}
                                                                                            <a class="dropdown-item text-primary upgrade-member"
                                                                                                data-wm-id="{{ $item->wm_id }}"
                                                                                                href="javascript:void(0);">
                                                                                                Thăng cấp thành viên
                                                                                            </a>
                                                                                        </li>
                                                                                        <li>
                                                                                            <a class="dropdown-item text-danger"
                                                                                                href="{{ route('activateMember', $item->wm_id) }}">Kích
                                                                                                thành
                                                                                                viên</a>
                                                                                        </li>
                                                                                    </ul>
                                                                                @elseif ($workspaceChecked->authorize == 'Sub_Owner')
                                                                                    <ul class="dropdown-menu"
                                                                                        aria-labelledby="dropdownMenuButton">
                                                                                        <li>
                                                                                            <a class="dropdown-item text-danger"
                                                                                                href="{{ route('activateMember', $item->wm_id) }}">Kích
                                                                                                thành
                                                                                                viên</a>
                                                                                        </li>
                                                                                    </ul>
                                                                                @endif

                                                                            </div>
                                                                        </div>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                            {{-- </div> --}}
                                                        </div>

                                                        @if ($workspaceChecked->authorize == 'Owner' || $workspaceChecked->authorize == 'Sub_Owner')
                                                            <div class="tab-pane" id="profile1" role="tabpanel">
                                                                {{-- <div class="scrollable-content"
                                                                style="max-height: 400px; overflow-y: auto;"> --}}
                                                                <ul style="margin-left: -32px; max-height: 400px; overflow-y: auto;"
                                                                    id="tab-ul-2">
                                                                    @foreach ($wspInvite as $item)
                                                                        <li class="d-flex justify-content-between"
                                                                            id="li_{{ $item->wm_id }}">
                                                                            <div class="col-1">
                                                                                <a href="javascript: void(0);"
                                                                                    class="avatar-group-item"
                                                                                    data-bs-toggle="tooltip"
                                                                                    data-bs-trigger="hover"
                                                                                    data-bs-placement="top"
                                                                                    title="Nancy">
                                                                                    @if ($item->image)
                                                                                        <img src="{{ Storage::url($item->image) ? Storage::url($item->image) : '' }}"
                                                                                            alt=""
                                                                                            class="rounded-circle avatar-xs" />
                                                                                    @else
                                                                                        <div class="bg-info-subtle rounded d-flex justify-content-center align-items-center"
                                                                                            style="width: 25px;height: 25px">
                                                                                            {{ strtoupper(substr($item->name, 0, 1)) }}
                                                                                        </div>
                                                                                    @endif
                                                                                </a>
                                                                            </div>
                                                                            <div class="col-7 d-flex flex-column">
                                                                                <section class="fs-12">
                                                                                    <p style="margin-bottom: 0px;"
                                                                                        class="text-black">
                                                                                        {{ $item->name }}
                                                                                        <span class="text-black">(Người
                                                                                            mới)</span>
                                                                                    </p>
                                                                                    <span>@ {{ $item->name }}</span>
                                                                                    <span><i
                                                                                            class="ri-checkbox-blank-circle-fill"></i></span>
                                                                                    <span>Đã gửi lời mời vào không gian làm
                                                                                        việc</span>
                                                                                </section>
                                                                            </div>
                                                                            <div class="col-4 d-flex justify-content-end">
                                                                                <form onsubmit="disableButtonOnSubmit()"
                                                                                    action="{{ route('accept_member') }}"
                                                                                    method="post">
                                                                                    @method('PUT')
                                                                                    @csrf
                                                                                    <input type="hidden"
                                                                                        value="{{ $item->user_id }}"
                                                                                        name="user_id">
                                                                                    <input type="hidden"
                                                                                        value="{{ $item->wm_id }}"
                                                                                        name="wsm_id">
                                                                                    <input type="hidden"
                                                                                        value="{{ $item->workspace_id }}"
                                                                                        name="workspace_id">
                                                                                    <button class="btn btn-primary me-2"
                                                                                        type="submit">Duyệt
                                                                                    </button>
                                                                                </form>
                                                                                <form
                                                                                    action="{{ route('refuse_member', $item->wm_id) }}"
                                                                                    onsubmit="disableButtonOnSubmit()"
                                                                                    method="post">
                                                                                    @method('DELETE')
                                                                                    @csrf
                                                                                    <button class="btn btn-danger"
                                                                                        type="submit">Từ chối
                                                                                    </button>
                                                                                </form>
                                                                            </div>
                                                                        </li>
                                                                        <br>
                                                                    @endforeach
                                                                </ul>
                                                                {{-- </div> --}}
                                                            </div>
                                                        @endif

                                                        <div class="tab-pane" id="profile2" role="tabpanel">
                                                            <ul style="margin-left: -32px;" id="tab-ul-3">
                                                                @foreach ($wspViewer as $item)
                                                                    <li class="d-flex justify-content-between"
                                                                        id="li_{{ $item->wm_id }}">
                                                                        <div class="col-1">
                                                                            <a href="javascript: void(0);"
                                                                                class="avatar-group-item"
                                                                                data-bs-toggle="tooltip"
                                                                                data-bs-trigger="hover"
                                                                                data-bs-placement="top" title="Nancy">
                                                                                @if ($item->image)
                                                                                    <img src="{{ Storage::url($item->image) ? Storage::url($item->image) : '' }}"
                                                                                        alt=""
                                                                                        class="rounded-circle avatar-xs" />
                                                                                @else
                                                                                    <div class="bg-info-subtle rounded d-flex justify-content-center align-items-center"
                                                                                        style="width: 25px;height: 25px">
                                                                                        {{ strtoupper(substr($item->name, 0, 1)) }}
                                                                                    </div>
                                                                                @endif
                                                                            </a>
                                                                        </div>
                                                                        <div class="col-7 d-flex flex-column">
                                                                            <section class="fs-12">
                                                                                <p style="margin-bottom: 0px;"
                                                                                    class="text-black">
                                                                                    {{ $item->name }}
                                                                                    <span class="text-black">(Người
                                                                                        xem)</span>
                                                                                </p>
                                                                                <span>@ {{ $item->name }}</span>
                                                                                <span><i
                                                                                        class="ri-checkbox-blank-circle-fill"></i></span>
                                                                                <span>Tham quan không gian làm việc</span>

                                                                            </section>
                                                                        </div>
                                                                        <div class="col-4 d-flex justify-content-end">
                                                                            <i class="ri-more-2-fill cursor-pointer ml-4"
                                                                                id="dropdownMenuButton"
                                                                                data-bs-toggle="dropdown"
                                                                                aria-expanded="false"></i>
                                                                            <ul class="dropdown-menu"
                                                                                aria-labelledby="dropdownMenuButton">
                                                                                <li>
                                                                                    <a class="dropdown-item text-primary addGuestWsp"
                                                                                        href="{{ route('addGuest', $item->wm_id) }}">Thêm
                                                                                        người dùng</a>
                                                                                </li>
                                                                                <li>
                                                                                    <a class="dropdown-item text-warning"
                                                                                        href="{{ route('deleteGuest', $item->wm_id) }}">Loại
                                                                                        người dùng</a>
                                                                                </li>
                                                                            </ul>
                                                                        </div>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>

                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <!--end row-->
                </div>
                <div class="card-body py-4">
                    <div class="mt-3">
                        <h2>Các cài đặt không gian làm việc</h2>
                        <div class="form-switch mt-3" style="margin-left: -30px">
                            <label class="form-check-label">Khả năng hiển thị trong không gian
                                làm việc</label>
                            <hr>
                        </div>
                    </div>
                    <div class="mt-3 d-flex justify-content-between">
                        <p class="col-10"><i class="{{ $icon }}"></i> {{ $access }} - {{ $ws_desrip }}
                        </p>

                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#customModal" style="height: 35px">
                            Mở cài đặt
                        </button>

                        <!-- Modal -->
                        <div class="modal fade" id="customModal" tabindex="-1" aria-labelledby="customModalLabel"
                            aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form id="updateAccessForm" action="{{ route('update_ws_access') }}" method="post">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="customModalLabel">Chọn khả năng hiển thị trong
                                                Không gian làm việc</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <!-- Privacy Options -->
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="access"
                                                    id="privateOption" value="private"
                                                    {{ $workspaceChecked->access == 'private' ? 'checked' : '' }}>
                                                <label class="form-check-label option-label" for="privateOption">
                                                    <i class="ri-lock-2-line fs-20 text-danger"></i>Riêng tư
                                                </label>
                                                <p class="option-description">
                                                    Đây là Không gian làm việc riêng tư. Chỉ những người trong Không
                                                    gian
                                                    làm việc có thể truy cập hoặc nhìn thấy Không gian làm việc.
                                                </p>
                                            </div>
                                            <hr>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="access"
                                                    id="publicOption" value="public"
                                                    {{ $workspaceChecked->access == 'public' ? 'checked' : '' }}>
                                                <label class="form-check-label option-label" for="publicOption">
                                                    <i class="ri-earth-line fs-20 text-success"></i>Công khai
                                                </label>
                                                <p class="option-description">
                                                    Đây là Không gian làm việc công khai. Bất kỳ ai có đường dẫn tới
                                                    Không
                                                    gian làm việc đều có thể nhìn thấy hoặc tìm thấy Không gian làm
                                                    việc.
                                                </p>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng
                                            </button>
                                            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                                        </div>
                                    </form>

                                    <div id="formResponse" class="mt-2"></div>


                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <a style="margin-left: 15px; padding-bottom:20px; " class="text-danger cursor-pointer fw-bold fs-16"
                    onclick="setDeleteAction()">
                    Xóa Không gian làm việc này?
                </a>

                {{-- <div class="modal fade" id="deleteWorkspaceModal" tabindex="-1"
                    aria-labelledby="deleteWorkspaceModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteWorkspaceModalLabel">Xác nhận xóa</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                Bạn có chắc chắn muốn xóa không gian làm việc này? Hành động này không thể hoàn tác.
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                <button type="button" id="confirmDeleteButton" class="btn btn-danger">Xóa</button>
                            </div>
                        </div>
                    </div>
                </div> --}}

                <div class="modal fade" id="deleteWorkspaceModal" tabindex="-1"
                    aria-labelledby="deleteWorkspaceModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteWorkspaceModalLabel">Xác nhận xóa</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Bạn có chắc chắn muốn xóa không gian làm việc này? Hành động này không thể hoàn
                                    tác.</p>
                                <p>Vui lòng nhập <strong id="workspaceNameConfirm"></strong> để xác nhận:</p>
                                <input type="text" id="workspaceNameInput" class="form-control"
                                    placeholder="Nhập tên không gian làm việc" />
                                <div id="errorText" class="text-danger mt-2" style="display: none;">Tên không đúng, vui
                                    lòng thử lại.
                                </div>
                                <input type="hidden" id="workspace" value="{{ $workspaceChecked->name }}">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                <button type="button" id="confirmDeleteButton" class="btn btn-danger" disabled>Xóa
                                </button>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>
        <!--end col-->
    </div>

@endsection

@section('title')
    Chỉnh sửa không gian làm việc
@endsection

@section('script')
    <script src="{{ asset('js/workspace.js') }}"></script>

    <script>
        function copyLink() {
            const link = '{{ $workspaceChecked->link_invite }}'; // Lấy link từ biến Laravel
            navigator.clipboard.writeText(link).then(function() {
                // Thay đổi icon sau khi sao chép thành công
                const copyIcon = document.getElementById('copy-icon');
                copyIcon.classList.remove('ri-attachment-2'); // Xóa icon hiện tại
                copyIcon.classList.add('ri-check-line'); // Thêm icon dấu kiểm

                // Thay đổi văn bản "Sao chép liên kết"
                const copyText = document.querySelector('span a');
                copyText.textContent = 'Đã sao chép';

                // Đặt thời gian chờ 20 giây trước khi chuyển icon và văn bản về trạng thái ban đầu
                setTimeout(function() {
                    // Khôi phục lại icon và văn bản sau 20 giây
                    copyIcon.classList.remove('ri-check-line');
                    copyIcon.classList.add('ri-attachment-2');
                    copyIcon.textContent = ''; // Xóa nội dung text nếu có

                    copyText.textContent = 'Sao chép liên kết';
                }, 5000); // 20000 milliseconds = 20 giây

            }).catch(function(error) {
                console.error('Error copying text: ', error);
                alert('Có lỗi xảy ra, vui lòng thử lại.');
            });
        }
    </script>


    {{-- delete workspace --}}
    {{-- để lại --}}
    <script>
        function setDeleteAction() {

            const correctString = '{{ $workspaceChecked->name }}';
            Swal.fire({
                title: 'Xóa không gian làm việc?',
                html: `
                <div style="text-align: left;">
                <strong>Nhập tên không gian làm việc "{{ $workspaceChecked->name }}" để xóa </strong>
                  <p class="fs-15">Những điều cần biết</p>
                     <ul class="fs-14">
                         <li>Điều này là vĩnh viễn và không thể hoàn tác.</li>
                         <li>Các bảng thuộc không gian làm việc bị xóa vĩnh viễn</li>
                     </ul>
                </div>
                `,
                input: 'text',
                inputPlaceholder: 'Nhập tên Không gian làm việc để xóa',
                {{-- inputValue: '{{$workspaceChecked->name}}', --}}
                showCancelButton: false,
                confirmButtonColor: "#d63036",
                confirmButtonText: 'Xóa không gian làm việc',
                preConfirm: (inputValue) => {
                    // Chuỗi chính xác mà bạn muốn so sánh

                    // Kiểm tra nếu inputValue khớp với chuỗi chính xác
                    if (inputValue !== correctString) {
                        // Nếu không khớp, hiển thị thông báo lỗi
                        Swal.showValidationMessage('Bạn nhập sai thông tin!!');
                        return false;
                    }
                    return true;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route('workspaces.delete', $workspaceChecked->workspace_id) }}',
                        type: "DELETE",
                        success: function(response) {
                            notificationWeb(response.action, response.msg);
                            if (response.action === 'success') {
                                setTimeout(() => {
                                    window.location.reload();
                                }, 2000)
                            }

                        },
                        error: function(xhr) {
                            console.log(xhr)
                            notificationWeb('error', 'Có lỗi xảy ra ròi');
                        },
                    });
                }
            });
        }
    </script>
    {{--
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const confirmDeleteButton = document.getElementById('confirmDeleteButton');
            const workspaceNameInput = document.getElementById('workspaceNameInput');
            const workspaceNameConfirm = document.getElementById('workspaceNameConfirm');
            const errorText = document.getElementById('errorText');

            // Gán tên không gian làm việc từ server
            const workspaceName = document.getElementById('workspace').value; // Thay bằng giá trị động từ server
            workspaceNameConfirm.textContent = 'Tên không gian làm việc';

            workspaceNameInput.addEventListener('input', function() {
                if (workspaceNameInput.value === workspaceName) {
                    confirmDeleteButton.disabled = false;
                    errorText.style.display = 'none';
                } else {
                    confirmDeleteButton.disabled = true;
                    errorText.style.display = 'block';
                }
            });

            confirmDeleteButton.addEventListener('click', function() {
                // Tiến hành xóa khi nhập đúng
                console.log('Không gian làm việc đã được xóa!');
                // Thực hiện submit form hoặc gọi API tại đây
            });
        });
    </script> --}}

    {{-- để lại --}}
    <script>
        $(document).ready(function() {
            $('#inviteButton').on('click', function(e) {
                e.preventDefault();

                // Lấy dữ liệu từ form
                let email = $('#submissionidInput').val();
                let authorize = $('#authorizeInput').val();
                let workspaceId = {{ $workspaceChecked->id }}; // ID của workspace

                // Gửi AJAX
                $.ajax({
                    url: '{{ route('invite_workspace', $workspaceChecked->id) }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        email: email,
                        authorize: authorize
                    },
                    beforeSend: function() {
                        $('#inviteButton').prop('disabled', true).text('Đang gửi');
                    },
                    success: function(response) {
                        notificationWeb(response.action, response.msg);

                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        alert('Đã xảy ra lỗi, vui lòng thử lại.');
                    },
                    complete: function() {
                        $('#inviteButton').prop('disabled', false).text('Chia sẻ');
                    }
                });
            });
        });
    </script>
@endsection
