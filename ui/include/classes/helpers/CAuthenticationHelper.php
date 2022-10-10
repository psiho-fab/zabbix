<?php declare(strict_types = 0);
/*
** Zabbix
** Copyright (C) 2001-2022 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


/**
 * A class for accessing once loaded parameters of Authentication API object.
 */
class CAuthenticationHelper extends CConfigGeneralHelper {

	public const AUTHENTICATION_TYPE = 'authentication_type';
	public const DEPROVISIONED_GROUPID = 'deprovisioned_groupid';
	public const HTTP_AUTH_ENABLED = 'http_auth_enabled';
	public const HTTP_CASE_SENSITIVE = 'http_case_sensitive';
	public const HTTP_LOGIN_FORM = 'http_login_form';
	public const HTTP_STRIP_DOMAINS = 'http_strip_domains';
	public const JIT_PROVISION_INTERVAL = 'jit_provision_interval';
	public const LDAP_AUTH_ENABLED = 'ldap_auth_enabled';
	public const LDAP_USERDIRECTORYID = 'ldap_userdirectoryid';
	public const LDAP_CASE_SENSITIVE = 'ldap_case_sensitive';
	public const LDAP_JIT_STATUS = 'ldap_jit_status';
	public const PASSWD_CHECK_RULES = 'passwd_check_rules';
	public const PASSWD_MIN_LENGTH = 'passwd_min_length';
	public const SAML_AUTH_ENABLED = 'saml_auth_enabled';
	public const SAML_CASE_SENSITIVE = 'saml_case_sensitive';
	public const SAML_JIT_STATUS = 'saml_jit_status';

	/**
	 * Authentication API object parameters array.
	 *
	 * @static
	 *
	 * @var array
	 */
	protected static array $params = [];

	/**
	 * Userdirectory API object parameters array.
	 *
	 * @static
	 *
	 * @var array
	 */
	protected static array $userdirectory_params = [];

	/**
	 * @inheritdoc
	 */
	protected static function loadParams(?string $param = null, bool $is_global = false): void {
		if (!self::$params) {
			self::$params = API::Authentication()->get(['output' => 'extend']);

			if (self::$params === false) {
				throw new Exception(_('Unable to load authentication API parameters.'));
			}
		}
	}

	/**
	 * Return Userdirectory API object by userdirectoryid.
	 *
	 * @param string $userdirectoryid
	 * @return array|null
	 *
	 * @throws Exception
	 */
	public static function getUserdirectory(string $userdirectoryid): ?array {
		if (!self::$userdirectory_params) {
			self::$userdirectory_params = API::getApiService('userdirectory')->getGlobal([
				'output' => 'extend',
				'selectProvisionGroups' => 'extend',
				'selectProvisionMedia' => 'extend'
			], false);

			if (!self::$userdirectory_params) {
				throw new Exception(_('Unable to load userdirectory API parameters.'));
			}
		}

		foreach (self::$userdirectory_params as $userdirectory) {
			if ($userdirectory['userdirectoryid'] == $userdirectoryid) {
				return $userdirectory;
			}
		}

		return null;
	}

	/**
	 * Return Userdirectory API object by idp type.
	 *
	 * @param string $idp_type
	 * @return array|null
	 *
	 * @throws Exception
	 */
	public static function getDefaultUserdirectory(string $idp_type): ?array {
		if (!self::$userdirectory_params) {
			self::$userdirectory_params = API::getApiService('userdirectory')->getGlobal([
				'output' => 'extend',
				'selectProvisionGroups' => 'extend',
				'selectProvisionMedia' => 'extend'
			], false);

			if (!self::$userdirectory_params) {
				throw new Exception(_('Unable to load userdirectory API parameters.'));
			}
		}

		if ($idp_type == IDP_TYPE_SAML) {
			foreach (self::$userdirectory_params as $userdirectory) {
				if ($userdirectory['idp_type'] == $idp_type) {
					return $userdirectory;
				}
			}
		}
		else {
			foreach (self::$userdirectory_params as $userdirectory) {
				if ($userdirectory['userdirectoryid'] == CAuthenticationHelper::get(
					CAuthenticationHelper::LDAP_USERDIRECTORYID
					)
				) {
					return $userdirectory;
				}
			}
		}

		return null;
	}

	/**
	 * Check is LDAP provisioning enabled for specific userdirectory:
	 * LDAP JIT provisioning is enabled, LDAP user directory provisioning is configured and enabled.
	 *
	 * @return bool
	 */
	public static function isLdapProvisionEnabled($userdirectoryid): bool {
		if ($userdirectoryid == 0 || self::get(self::LDAP_JIT_STATUS) != JIT_PROVISIONING_ENABLED) {
			return false;
		}

		return API::UserDirectory()->get([
			'countOutput' => true,
			'userdirectoryids' => [$userdirectoryid],
			'filter' => ['provision_status' => JIT_PROVISIONING_ENABLED, 'idp_type' => IDP_TYPE_LDAP]
		]) > 0;
	}

	/**
	 * Check is the given timestamp require user provisioning according jit_provision_interval.
	 *
	 * @param int $timestamp
	 *
	 * @return bool Is true when given timestamp require provisioning.
	 */
	public static function isTimeToProvision($timestamp): bool {
		$jit_interval = timeUnitToSeconds(self::get(self::JIT_PROVISION_INTERVAL));

		return ($timestamp + $jit_interval) < time();
	}
}
