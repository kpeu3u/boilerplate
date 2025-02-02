<?php

namespace agungsugiarto\boilerplate\Filters;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Myth\Auth\Config\Services;

/**
 * Class RoleFilter.
 */
class RoleFilter implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default, it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param array|null $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! function_exists('logged_in')) {
            helper('auth');
        }

        if (empty($arguments)) {
            return null;
        }

        $authenticate = Services::authentication();

        // if no user is logged in then send to the login form
        if (! $authenticate->check()) {
            session()->set('redirect_url', current_url());

            return redirect('login');
        }

        $authorize = Services::authorization();

        // Check each requested permission
        foreach ($arguments as $group) {
            if ($authorize->inGroup($group, $authenticate->id())) {
                return null;
            }
        }

        if ($authenticate->silent()) {
            $redirectURL = session('redirect_url') ?? '/';
            unset($_SESSION['redirect_url']);

            return redirect()->to($redirectURL)->with('error', lang('Auth.notEnoughPrivilege'));
        }

        throw PageNotFoundException::forPageNotFound(lang('Auth.notEnoughPrivilege'));
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param null $arguments
     *
     * @return void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
