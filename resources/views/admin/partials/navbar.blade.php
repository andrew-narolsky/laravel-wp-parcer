<ul class="navbar-nav navbar-nav-right">
    <li class="nav-item nav-logout d-none d-lg-block">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button
                type="submit"
                class="nav-link"
            >
                <i class="mdi mdi-power"></i>
            </button>
        </form>
    </li>
</ul>
<button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button"
        data-toggle="offcanvas">
    <span class="mdi mdi-menu"></span>
</button>
