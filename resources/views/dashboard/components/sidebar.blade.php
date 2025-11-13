<ul
    class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion"
    id="accordionSidebar"
>
    <!-- Sidebar - Brand -->
    <a
        class="sidebar-brand d-flex align-items-center justify-content-center"
        href="index.html"
    >
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-folder-open"></i>
        </div>
        <div class="sidebar-brand-text mx-3">GPU GE <sup>docs</sup></div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0" />

    <!-- Nav Item - Dashboard -->
    <li class="nav-item {{ Request::routeIs('dashboard') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('dashboard') }}">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider" />

    <!-- Heading -->
    <div class="sidebar-heading">Menu Dokumen</div>

    <!-- Nav Item - Pages Collapse Menu -->
    <!-- Berita Acara -->
    <!-- <li class="nav-item">
        <a
            class="nav-link collapsed"
            href="#"
            data-toggle="collapse"
            data-target="#collapseTwo"
            aria-expanded="true"
            aria-controls="collapseTwo"
        >
            <i class="fas fa-file-alt"></i>
            <span>Berita Acara</span>
        </a>
        <div
            id="collapseTwo"
            class="collapse"
            aria-labelledby="headingTwo"
            data-parent="#accordionSidebar"
        >
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Berita Acara:</h6>
                <a class="collapse-item" href="{{ route('dashboard.documents.index') }}">
                    <i class="fas fa-truck-moving"></i>
                    BA Hauling
                </a>
                <a class="collapse-item" href="cards.html">
                    <i class="fas fa-tools"></i>
                    BA Rental
                </a>
            </div>
        </div>
    </li> -->

    <li class="nav-item {{ Request::routeIs('dashboard.documents*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('dashboard.documents.index') }}">
            <i class="fas fa-file-alt"></i>
            <span>All BA</span>
        </a>
    </li>

    <li class="nav-item {{ Request::routeIs('dashboard.documents-approvals*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('dashboard.documents-approvals.index') }}">
            <i class="fas fa-check-double"></i>
            <span>BA Approvals</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="fas fa-file-archive"></i>
            <span>Arsip Dokumen</span>
        </a>
    </li>

    <hr class="sidebar-divider" />

    @role('super-admin')

    <div class="sidebar-heading">Menu Admin</div>

    <!-- Category Document -->
    <li class="nav-item {{ Request::routeIs('dashboard.categories*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('dashboard.categories.index') }}">
            <i class="fas fa-folder-open"></i>
            <span>Category Document</span>
        </a>
    </li>

    <!-- Users -->
    <li class="nav-item {{ Request::routeIs('dashboard.users*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('dashboard.users.index') }}">
            <i class="fas fa-users-cog"></i>
            <span>Users</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block" />
    @endrole

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
</ul>
