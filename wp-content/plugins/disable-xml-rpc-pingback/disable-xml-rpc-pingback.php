<?php
/*
Plugin Name: Disable XML-RPC Pingback
Description: Stops abuse of your site's Pingback method from XML-RPC by simply removing it. While you can use the rest of XML-RPC methods.
Author: Samuel Aguilera
Version: 1.0
Author URI: http://www.samuelaguilera.com
License: GPL2
*/

/*
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License version 2 as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

add_filter( 'xmlrpc_methods', 'Remove_Pingback_Method' );

function Remove_Pingback_Method( $methods ) {
   unset( $methods['pingback.ping'] );
   unset( $methods['pingback.extensions.getPingbacks'] );
   return $methods;
}

?>