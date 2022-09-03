<?php

/**
 * @version 2022.08.29.
 * @package SSE szállásfoglaló
 * @author Soós András
 */

session_start(); 
require_once 'frontend.php';
$frontend = new frontend();
print $frontend->show_output();

/**
 * <TODO OPCIÓK>
 * Hány órával előtte lehessen foglalni?
 * Hány férőhely van (Bent/Kint)?
 */

?>
