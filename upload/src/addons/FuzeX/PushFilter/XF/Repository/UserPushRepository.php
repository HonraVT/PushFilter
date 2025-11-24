<?php

namespace FuzeX\PushFilter\XF\Repository;

class UserPushRepository extends XFCP_UserPushRepository
{
    /**
     * Extends the original validation method
     */
    public function validateSubscriptionDetails(array $subscription, &$error = null)
    {
        // 1. Runs default XenForo validation first.
        $parentValid = parent::validateSubscriptionDetails($subscription, $error);
        if (!$parentValid)
        {
            return false;
        }

        // 2. Gets allowed hosts from options (cached by XenForo)
        $allowedHostsOption = \XF::options()->push_filter_allowed_hosts ?? '';
        $allowedPushHosts = array_filter(array_map('trim', explode("\n", $allowedHostsOption)));

        // If no hosts are configured, allow all (or set an error if preferred)
        if (empty($allowedPushHosts)) {
            return true;
        }

        // 3. Host validation
        $endpointUrl = $subscription['endpoint'] ?? '';
        $host = parse_url($endpointUrl, PHP_URL_HOST);

        if ($host && $this->isHostAllowed($host, $allowedPushHosts))
        {
            return true;
        }

        // 4. if not valid return false
        return false;
    }

    /**
     * Helper function to check the host against the allowlist
     */
    protected function isHostAllowed($host, array $allowedPushHosts)
    {
        foreach ($allowedPushHosts as $pattern)
        {
            if (fnmatch($pattern, $host, FNM_CASEFOLD))
            {
                return true;
            }
        }
        return false;
    }
}
