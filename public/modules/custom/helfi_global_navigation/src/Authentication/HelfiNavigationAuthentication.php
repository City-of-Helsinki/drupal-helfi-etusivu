<?php

namespace Drupal\helfi_global_navigation\Authentication;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Session\UserSession;
use Symfony\Component\HttpFoundation\Request;

/**
 * Authentication for helfi navigation endpoints.
 */
class HelfiNavigationAuthentication implements AuthenticationProviderInterface {

  /**
   * {@inheritDoc}
   */
  public function applies(Request $request) {
    // @todo Proper authentication logic.
    // Request ought to have some kind of token check.
    return str_contains($request->getRequestUri(), '/global-menus/');
    // Return $request->headers->has('X-Auth-Token');.
  }

  /**
   * {@inheritDoc}
   */
  public function authenticate(Request $request) {
    $token = $request->headers->get('X-Auth-Token');

    // @todo Proper authentication logic.
    // Accept only from sites mentioned in environment resolver.
    // Request ought to have some kind of token or cert check.
    // Return a session if the request passes the validation.
    return new UserSession();
  }

}
