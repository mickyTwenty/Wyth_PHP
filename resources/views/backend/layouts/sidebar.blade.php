    <section class="sidebar">
          <!-- Sidebar user panel -->
          <div class="user-panel">
            <div class="pull-left image">
              <img src="{{ public_url( user()->profile_picture_path ) }}" class="img-circle" alt="User Image">
            </div>
            <div class="pull-left info">
              <p>{{ user()->full_name }}</p>
              <a href="{{ url('/') }}" target="_blank">(View front website)</a>
            </div>
          </div>

          <!-- sidebar menu: : style can be found in sidebar.less -->
        <ul class="sidebar-menu">
            {!! backend_sidebar_generator( constants('back.sidebar.menu'), [
                'dealers'         => ($isAdmin = user()->isAdmin()),
                'settings'        => $isAdmin,
                'pages'           => $isAdmin,
                'agreement'       => $isAdmin,
            ] ) !!}
          </ul>
    </section>
