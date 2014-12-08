<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Core
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$configValuesMap = array(
    'catalog/productalert/email_stock_template'         => 'catalog_productalert_email_stock_template',
    'catalog/productalert_cron/error_email_template'    => 'catalog_productalert_cron_error_email_template',
    'contacts/email/email_template'                     => 'contacts_email_email_template',
    'customer/create_account/email_template'            => 'customer_create_account_email_template',
    'customer/password_forgot/email_template'           => 'customer_password_forgot_email_template',
    'newsletter/subscription/confirm_email_template'    => 'newsletter_subscription_confirm_email_template',
    'newsletter/subscription/success_email_template'    => 'newsletter_subscription_success_email_template',
    'newsletter/subscription/un_email_template'         => 'newsletter_subscription_un_email_template',
     
    'sitemap/generate/error_email_template'             => 'sitemap_generate_error_email_template',
     
);

foreach ($configValuesMap as $configPath=>$configValue) {
    $installer->setConfigData($configPath, $configValue);
}
