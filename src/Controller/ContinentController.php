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
    //$ip = '73.111.47.90'; //us ip
    //$ip = '185.208.164.108'; // EU IP
    $config = \Drupal::config('gdpr_tag_manager.settings');
    $ip_service = $config->get('ip_service');

    if ($config->get('activate') == 1) {
      if (isset($_COOKIE['isNA'])) {
        $ip_data['c_code'] = 'NA';
      } else {
        $ip_data['c_code'] = $this::ContinentController_get_country_code($ip, $ip_service);
      }
      $ip_data['isanon'] = \Drupal::currentUser()->isAnonymous() ? TRUE : FALSE;
    }
    return JsonResponse::create($ip_data);
  }

  /**
   * Get continent code from external free service for eu c_code.
   */
  function ContinentController_get_country_code($ip, $ip_service) {
    try {
      switch ($ip_service) {
      case 'IPAPI':
        $uri = 'https://ipapi.co/' . $ip . '/json';
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
      }
      else {
        return [];
      }
    } catch (\GuzzleHttp\Exception\ClientException $e) {
      $message = $e->getMessage() . '. Make sure you provided correct IP to get country code .';
      \Drupal::logger('gdpr_tag_manager_get_country_code')->notice($message);
      return [];
    }
  }
}
