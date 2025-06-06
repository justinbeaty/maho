<?php

/**
 * Maho
 *
 * @package    Mage_Api2
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2023 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Api2_Model_Auth_Adapter_Oauth extends Mage_Api2_Model_Auth_Adapter_Abstract
{
    /**
     * Process request and figure out an API user type and its identifier
     *
     * Returns stdClass object with two properties: type and id
     *
     * @return stdClass
     */
    #[\Override]
    public function getUserParams(Mage_Api2_Model_Request $request)
    {
        /** @var Mage_Oauth_Model_Server $oauthServer */
        $oauthServer   = Mage::getModel('oauth/server', $request);
        $userParamsObj = (object) ['type' => null, 'id' => null];

        try {
            $token    = $oauthServer->checkAccessRequest();
            $userType = $token->getUserType();

            if (Mage_Oauth_Model_Token::USER_TYPE_ADMIN == $userType) {
                $userParamsObj->id = $token->getAdminId();
            } else {
                $userParamsObj->id = $token->getCustomerId();
            }
            $userParamsObj->type = $userType;
        } catch (Exception $e) {
            throw new Mage_Api2_Exception($oauthServer->reportProblem($e), Mage_Api2_Model_Server::HTTP_UNAUTHORIZED);
        }
        return $userParamsObj;
    }

    /**
     * Check if request contains authentication info for adapter
     *
     * @return bool
     */
    #[\Override]
    public function isApplicableToRequest(Mage_Api2_Model_Request $request)
    {
        $headerValue = $request->getHeader('Authorization');

        return $headerValue && strtolower(substr($headerValue, 0, 5)) === 'oauth';
    }
}
