<!-- ========== Left Sidebar Start ========== -->
<style>
    body[data-sidebar-size=sm] .vertical-menu {
        position: fixed !important;
    }
</style>

<div class="vertical-menu">

    <!-- LOGO -->
    <div class="navbar-brand-box">
        <a href="{{ url('/') }}" class="logo logo-dark">
            <span class="logo-sm">
                <img src="{{ rbac_asset('images/logo-sm.png') }}" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="{{ rbac_asset('images/logo-dark.png') }}" alt="" height="20">
            </span>
        </a>

        <a href="{{ url('/') }}" class="logo logo-light">
            <span class="logo-sm">
                <img src="{{ rbac_asset('images/logo-sm.png') }}" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="{{ rbac_asset('images/logo-light.png') }}" alt="" height="20">
            </span>
        </a>
    </div>

    <button type="button" class="btn btn-sm px-3 font-size-16 header-item waves-effect vertical-menu-btn">
        <i class="fa fa-fw fa-bars"></i>
    </button>

    <div data-simplebar class="sidebar-menu-scroll">

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title">Menu</li>

                @can('dashboard-view')
                <li class="{{ (request()->is('/') || request()->routeIs('dashboard') || request()->is('dashboard')) ? 'mm-active' : '' }}">
                    <a href="{{ Route::has('dashboard') ? route('dashboard') : url('/') }}" class="{{ (request()->is('/') || request()->routeIs('dashboard') || request()->is('dashboard')) ? 'active' : '' }}">
                        <i class="uil-home-alt"></i><span class="badge rounded-pill bg-primary float-end">01</span>
                        <span>Dashboard</span>
                    </a>
                </li>
                @endcan

                @canany(['user-view', 'role-view'])
                    @php
                        $isMasterActive = request()->routeIs('roles.*') || request()->routeIs('master.user.*');
                    @endphp
                    <li class="{{ $isMasterActive ? 'mm-active' : '' }}">
                        <a href="javascript: void(0);" class="has-arrow waves-effect {{ $isMasterActive ? 'active' : '' }}" aria-expanded="{{ $isMasterActive ? 'true' : 'false' }}">
                            <i class="uil-window-section"></i>
                            <span>Master</span>
                        </a>
                        <ul class="sub-menu {{ $isMasterActive ? 'mm-show' : '' }}" aria-expanded="{{ $isMasterActive ? 'true' : 'false' }}">
                            @can('role-view')
                                <li class="{{ request()->routeIs('roles.*') ? 'mm-active' : '' }}">
                                    <a href="{{ route('roles.index') }}" class="{{ request()->routeIs('roles.*') ? 'active' : '' }}">
                                        <span>Roles</span>
                                    </a>
                                </li>
                            @endcan
                            @can('user-view')
                                <li class="{{ request()->routeIs('master.user.*') ? 'mm-active' : '' }}">
                                    <a href="{{ route('master.user.index') }}" class="{{ request()->routeIs('master.user.*') ? 'active' : '' }}">
                                        <span>Users</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @canany(['permission-view'])
                    <li class="menu-title">Access Control</li>
                @endcanany
                @can('permission-view')
                <li class="{{ request()->routeIs('permissions.*') ? 'mm-active' : '' }}">
                    <a href="{{ route('permissions.index') }}" class="waves-effect {{ request()->routeIs('permissions.*') ? 'active' : '' }}">
                        <i class="uil uil-key-skeleton"></i>
                        <span>Permissions</span>
                    </a>
                </li>
                @endcan

                @canany(['auth_setting-view'])
                    @php
                        $isSettingsActive = request()->routeIs('auth.settings*');
                    @endphp
                    <li class="{{ $isSettingsActive ? 'mm-active' : '' }}">
                        <a href="javascript: void(0);" class="has-arrow waves-effect {{ $isSettingsActive ? 'active' : '' }}" aria-expanded="{{ $isSettingsActive ? 'true' : 'false' }}">
                            <i class="fas fa-cogs"></i>
                            <span>Settings</span>
                        </a>
                        <ul class="sub-menu {{ $isSettingsActive ? 'mm-show' : '' }}" aria-expanded="{{ $isSettingsActive ? 'true' : 'false' }}">
                            @can('auth_setting-view')
                                <li class="{{ request()->routeIs('auth.settings*') ? 'mm-active' : '' }}">
                                    <a href="{{ route('auth.settings') }}" class="{{ request()->routeIs('auth.settings*') ? 'active' : '' }}">
                                        <span>Auth Setting</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
<!-- Left Sidebar End -->
