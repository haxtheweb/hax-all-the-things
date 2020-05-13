<?php
/**
 * Class for implementing web component polyfills and other header aspects in a uniform manner
 */
class WebComponentsService {
  /**
   * This applies all pieces of a standard build appended to the header
   */
  public function applyWebcomponents($directory = '/', $cdn = '/') {
    return $this->getBuild($directory, "false", $cdn);
  }
  /**
   * Front end logic for ES5-AMD, ES6-AMD, ES6 version to deliver
   */
  public function getBuild($directory  = '/', $forceUpgrade = "false", $cdn = '/') {
    return '
    <script type="text/javascript">
      window.__appCDN="' . $cdn . '";
      window.__appForceUpgrade=' . $forceUpgrade . ';
    </script>
    <script type="text/javascript" src="' . $directory . 'build.js"></script>';
  }
}