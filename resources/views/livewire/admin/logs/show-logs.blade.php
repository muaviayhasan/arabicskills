<div>

    <x-admin.list-toolbar>
        <input type="text" wire:model.live.debounce.300="searchWord" class="form-control bg-light border-light rounded"
            placeholder="Search email or username" style="max-width: 280px;">
    </x-admin.list-toolbar>

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-centered align-middle table-nowrap table-check"
                            style="margin-bottom: 70px; !important">
                            <thead>
                                <tr class="fw-semibold text-center">

                                    <x-admin.sortable-header field="type" label="User Type"
                                        :current="$sortField" :direction="$sortDirection" />
                                    <x-admin.sortable-header field="username" label="Email/UserName"
                                        :current="$sortField" :direction="$sortDirection" />
                                    <x-admin.sortable-header field="location" label="Location"
                                        :current="$sortField" :direction="$sortDirection" />
                                    <x-admin.sortable-header field="ip" label="IP"
                                        :current="$sortField" :direction="$sortDirection" />
                                    <x-admin.sortable-header field="created_at" label="Login At"
                                        :current="$sortField" :direction="$sortDirection" />

                                </tr>
                            </thead>
                            <tbody>

                                @forelse ($this->logs as $log)
                                <tr class="text-center">

                                    <td class="">
                                        {{ $log->loggable_type =="App\Models\Admin" ? 'Admin':"Student" }}
                                    </td>

                                    <td class="fw-semibold">
                                        {{ $log->username }}
                                    </td>

                                    <td class="">
                                        {{ $log->location }}
                                    </td>

                                    <td class="">
                                        {{ $log->ip }}
                                    </td>

                                    <td class="">
                                        {{ $log->created_at->format('d M Y h:i A') }}
                                    </td>
                                </tr>
                                @empty
                                <tr class="text-center">
                                    <td class="bg-white" colspan="5">

                                        <img src="{{ asset('assets/images/empty.png') }}" alt="Empty List Image"
                                            width="25%">
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3">
                        <div class="float-end">
                            {{ $this->logs->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>