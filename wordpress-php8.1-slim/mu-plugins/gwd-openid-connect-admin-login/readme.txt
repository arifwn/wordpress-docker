=== GWD OpenID Connect Administators Login ===
Contributors: daggerhart, tnolte, arifwn
Donate link: 
Tags: security, login, oauth2, openidconnect, apps, authentication, autologin, sso
Requires at least: 4.9
Tested up to: 6.0.0
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Grover Web Design administrative login via OpenID Connect.

== Description ==

This plugin allows Grover Web Design's administators to login using OpenID Connect.

Based on: https://github.com/daggerhart/gwd-admin-login

== Installation ==

1. Make sure the container is setup to include OpenID client credential (OPENID_CONNECT_CLIENT_ID, OPENID_CONNECT_CLIENT_SECRET and OPENID_CONNECT_REALM) as environmental variables
1. Upload to the `/wp-content/plugins/` directory
1. Activate the plugin
1. Visit Settings > GWD OpenID Connect and configure to meet your needs

== Changelog ==

= 1.0.0 =

Initial version
