<?php
/**
 * @file
 * Contains \Drupal\gdpr_tag_manager\Controller\ContinentController.
 */

namespace Drupal\gdpr_tag_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use GuzzleHttp\Client;

/**
 * Class ContinentController.
 *
 * @package Drupal\gdpr_tag_manager\Controller
 */
class ContinentController extends ControllerBase {

  /**
   * Autocomplete.
   */
  public function getContinentCode() {
    $ip = \Drupal::request()->getClientIp();
    $config = \Drupal::config('gdpr_tag_manager.settings');
    $ip_service = $config->get('ip_service');
    $ipapi_key = $config->get('ipapi_key');
    $is_bot = $this::smart_ip_detect_crawler($_SERVER['HTTP_USER_AGENT']);
    if ($config->get('activate') == 1 && $is_bot == FALSE) {
      if (isset($_COOKIE['isNA'])) {
        $ip_data['c_code'] = 'NA';
      } else {
        $ip_data['c_code'] = $this::ContinentController_get_country_code($ip, $ip_service, $ipapi_key);
      }
      $ip_data['isanon'] = \Drupal::currentUser()->isAnonymous() ? TRUE : FALSE;
    }
    return JsonResponse::create($ip_data);
  }

  /**
   * Get continent code from either IPAPI or GEOIP
   */
  function ContinentController_get_country_code($ip, $ip_service, $ipapi_key = NULL) {
    try {
      switch ($ip_service) {
      case 'IPAPI':
        $uri = 'https://ipapi.co/' . $ip . '/json?key=' . $ipapi_key;
        break;
      case 'GEOIP':
        $uri = 'http://www.geoplugin.net/json.gp?ip=' . $ip;
        break;
      }
      $client = \Drupal::httpClient(['base_url' => $uri]);
      $request = $client->request('GET', $uri, [
        'timeout' => 5,
        'headers' => ['Accept' => 'application/json'],
      ]);
      if ($request->getStatusCode() == 200) {
        $response = json_decode($request->getBody());
        if (empty($response)) {
          return [];
        }
        else {
          if ($ip_service == 'GEOIP') {
            return ($response->geoplugin_continentCode);
          } elseif ($ip_service == 'IPAPI') {
            return ($response->continent_code);
          }
        }
      } else if ($request->getStatusCode() == 429) {

      } else {
        return [];
      }
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        $message = $e->getMessage() . '. Make sure you provided correct IP to get country code .';
        \Drupal::logger('gdpr_tag_manager_get_country_code')->notice($message);
        return [];
    }
  }

  function smart_ip_detect_crawler($user_agent) {
    // User lowercase string for comparison.
    $user_agent = strtolower($user_agent);
    // A list of some common words used only for bots and crawlers.
    $bot_identifiers = [
      'bot',
      'slurp',
      'crawler',
      'spider',
      'curl',
      'facebook',
      'fetch',
    ];
    // See if one of the identifiers is in the UA string.
    foreach ($bot_identifiers as $identifier) {
      if (strpos($user_agent, $identifier) !== FALSE) {
        return TRUE;
      }
    }
    return FALSE;
  }
}
