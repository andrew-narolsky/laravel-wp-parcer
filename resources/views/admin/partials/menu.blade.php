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
</ul>
