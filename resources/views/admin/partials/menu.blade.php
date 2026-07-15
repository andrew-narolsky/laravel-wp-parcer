<ul class="nav">
    <li class="nav-item sidebar-actions">
        <span class="nav-link fw-bold">GENERAL</span>
    </li>
    <li class="nav-item @ifroute('admin') active @endifroute">
        <a class="nav-link" href="{{ route('admin') }}">
            <span class="menu-title">Dashboard</span>
            <i class="mdi mdi-home menu-icon"></i>
        </a>
    </li>
    <li class="nav-item @ifroute('admin.sites.*') active @endifroute">
        <a class="nav-link" href="{{ route('admin.sites.index') }}">
            <span class="menu-title">Sites</span>
            <i class="mdi mdi-web menu-icon"></i>
        </a>
    </li>
    <li class="nav-item @ifroute('admin.links.*') active @endifroute">
        <a class="nav-link" href="{{ route('admin.links.index') }}">
            <span class="menu-title">Links</span>
            <i class="mdi mdi-link menu-icon"></i>
        </a>
    </li>
    <li class="nav-item @ifroute('admin.backups.*') active @endifroute">
        <a class="nav-link" href="{{ route('admin.backups.index') }}">
            <span class="menu-title">Backups</span>
            <i class="mdi mdi-database menu-icon"></i>
        </a>
    </li>
</ul>
