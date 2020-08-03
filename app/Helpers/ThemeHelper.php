<?php

function constants($key, $default = null)
{
    return config( 'constants.' . $key, $default );
}

function backend_view($file)
{
    return call_user_func_array( 'view', ['backend/' . $file] + func_get_args() );
}

function backend_path($uri='')
{
    return public_path( 'backend/' . $uri );
}

function backend_asset($uri='')
{
    return asset( 'backend/assets/' . ltrim($uri,'/') );
}

function backend_public($uri='')
{
    return asset( 'backend/' . ltrim($uri,'/') );
}

function backend_url($uri='/')
{
    return call_user_func_array( 'url', ['backend/' . ltrim($uri,'/')] + func_get_args() );
}

function frontend_view($file)
{
    return call_user_func_array( 'view', ['frontend/' . $file] + func_get_args() );
}

function frontend_path($uri='')
{
    return public_path( 'frontend/' . $uri );
}

function frontend_asset($uri='')
{
    return asset( 'frontend/assets/' . ltrim($uri,'/') );
}

function frontend_url($uri='/')
{
    return call_user_func_array( 'url', ['' . ltrim($uri,'/')] + func_get_args() );
}

function public_url($uri='/')
{
    return call_user_func_array( 'asset', ['/' . ltrim($uri,'/')] + func_get_args() );
}

/**
 * Generate sidebar html by php array menu
 */
function backend_sidebar_generator($constantMenu, $condition=array())
{
    $currentRequest = Illuminate\Support\Facades\Request::getPathInfo();
    $currentRequest = substr($currentRequest, 0, 8) == '/backend' ? substr($currentRequest, 8) : $currentRequest;

    $li = [];
    foreach ($constantMenu as $menuKey => $menu) {

        # Ability to hide/show any menu with conditions, alias must be defined as key in constant file
        if ( is_array($condition) && isset($condition[$menuKey]) && $condition[$menuKey] === false )
            continue;

        if ( isset($menu['populate']) && $menu['populate'] === false )
            continue;

        # Is label for heading?
        if ( isset($menu['type']) && $menu['type'] == 'heading' ) {
            $li[] = '<li class="header">'.$menu['label'].'</li>';
            continue;
        }

        $hrefUrl  = substr($menu['path'], 0, 1) == '/' ? backend_url( $menu['path'] ) : $menu['path'];
        $isActive = ($menu['path'] == $currentRequest);

        if ( isset($menu['regexPath']) && $menu['regexPath'] ) {
            $isActive = preg_match($menu['regexPath'], $currentRequest) === 1;
        }

        if ( array_key_exists('submenu', $menu) ) {

            # Active class not found, now lets check if submenu links matched?
            foreach ($menu['submenu'] as $subMenu) {
                if ( !$isActive ) {
                    $isActive = $subMenu['path'] == $currentRequest;
                    if ( !$isActive && isset($subMenu['regexPath']) && $subMenu['regexPath'] ) {
                        $isActive = preg_match($subMenu['regexPath'], $currentRequest) === 1;
                    }
                }

                if ( $isActive ) {
                    break;
                }
            }

            $li[] = '<li class="treeview' . ($isActive ? ' active' : '') . '">
              <a href="'.$hrefUrl.'">
                <i class="'.$menu['icon'].'"></i> <span>'.$menu['label'].'</span>
                <span class="pull-right-container">
                  <i class="fa fa-angle-left pull-right"></i>
                </span>
              </a>
              <ul class="treeview-menu">
                ' . backend_sidebar_generator($menu['submenu'], $condition) . '
              </ul>
            </li>';
        } else {

            $li[] = '<li' . ($isActive ? ' class="active"' : '') . '><a href="' . $hrefUrl . '"><i class="' . (isset($menu['icon']) ? $menu['icon'] : '') . '"></i> <span>' . $menu['label'] . '</span></a></li>';
        }
    }

    return implode("\n\t\t", $li);
}

/**
 * A funciton to identify if current route lies under expected routes group
 *
 * @param  String $currentRoute
 * @param  Array  $possibleRoutes
 * @return Boolean
 */
function parseRoute( $currentRoute, Array $possibleRoutes )
{
    foreach ($possibleRoutes as $route) {
        if (preg_match('%'.$route.'%', $currentRoute))
            return true;
    }

    return false;
}

if ( ! function_exists('user') ) {
    function user()
    {
        return Auth::user();
    }
}
