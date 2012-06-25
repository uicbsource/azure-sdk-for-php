<?php

/**
 * LICENSE: Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * PHP version 5
 *
 * @category  Microsoft
 * @package   Tests\Framework
 * @author    Azure PHP SDK <azurephpsdk@microsoft.com>
 * @copyright 2012 Microsoft Corporation
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link      https://github.com/WindowsAzure/azure-sdk-for-php
 */
namespace Tests\Framework;
use Tests\Framework\TestResources;
use WindowsAzure\Common\Configuration;
use WindowsAzure\Common\Models\ServiceProperties;
use WindowsAzure\ServiceBus\ServiceBusSettings;
use WindowsAzure\ServiceBus\ServiceBusRestProxy;
use WindowsAzure\ServiceBus\IServiceBus;
use WindowsAzure\ServiceBus\Models\SubscriptionInfo;

/**
 * TestBase class for each unit test class.
 *
 * @category  Microsoft
 * @package   Tests\Framework
 * @author    Azure PHP SDK <azurephpsdk@microsoft.com>
 * @copyright 2012 Microsoft Corporation
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @version   Release: @package_version@
 * @link      https://github.com/WindowsAzure/azure-sdk-for-php
 */
class ServiceBusRestProxyTestBase extends ServiceRestProxyTestBase
{
    private $_createdTopics; 
    private $_createdSubscriptions; 
    private $_createdRules;
    private $_createdQueues;

    public static function setUpBeforeClass()
    {
        if (Configuration::isEmulated()) {
            throw new \Exception(self::NOT_SUPPORTED);
        }

        $serviceBusNameSpace = TestResources::serviceBusNameSpace();
        $wrapAuthenticationName = TestResources::wrapAuthenticationName();
        $wrapPassword = TestResources::wrapPassword();

        if (empty($serviceBusNameSpace)) {
            throw new \Exception('SERVICE_BUS_NAMESPACE enviroment variable is missing.');
        }

        if (empty($wrapAuthenticationName)) {
            throw new \Exception('WRAP_AUTHENTICATION_NAME enviroment variable is missing.');
        }

        if (empty($wrapPassword)) {
            throw new \Exception('WRAP_PASSWORD enviroment variable is missing.');
        }

    }

    public function setUp()
    {
        $config = new Configuration();
        $config = ServiceBusSettings::configureWithWrapAuthentication(
            $config,
            TestResources::serviceBusNameSpace(),
            TestResources::wrapAuthenticationName(),
            TestResources::wrapPassword()
        );

        $serviceBusWrapper = $this->builder->createServiceBusService($config);
        $this->_createdTopics = array();
        $this->_createdSubscriptions = array();
        $this->_createdRules = array();
        $this->_createdQueues = array();
        parent::setUp($serviceBusWrapper);
    }
    
    public function createQueue($queueInfo)
    {
        $this->_createdQueues[] = $queueInfo->getTitle();
        return $this->restProxy->createQueue($queueInfo);
    }
    
    public function createTopic($topicInfo)
    {
        $this->_createdTopics[] = $topicInfo->getTitle();
        return $this->restProxy->createTopic($topicInfo);
    }

    public function createSubscription($topicName, $subscriptionInfo) 
    {
        $topicSubscriptionNameArray = array($topicName, $subscriptionInfo->getTitle());
        $this->_createdSubscriptions[] = join('::', $topicSubscriptionNameArray);
        return $this->restProxy->createSubscription($topicName, $subscriptionInfo);
    }

    public function createRule($topicName, $subscriptionName, $ruleInfo) 
    {
        $topicSubscriptionRuleArray = array($topicName, $subscriptionName, $ruleInfo->getTitle());
        $this->_createdRules[] = join('::', $topicSubscriptionRuleArray);
        return $this->restProxy->createRule($topicName, $subscriptionName, $ruleInfo);
    }

    public function safeDeleteQueue($queueName)
    {   
        try
        {
            $this->restProxy->deleteQueue($queueName);
        }
        catch (\Exception $e)
        {
            error_log($e->getMessage());
        }
    }

    public function safeDeleteRule($topicName, $subscriptionName, $ruleName)
    {
        try
        {
            $this->restProxy->deleteRule($topicName, $subscriptionName, $ruleName);
        }
        catch (\Exception $e)
        {
            error_log($e->getMessage());
        }
    }

    public function safeDeleteSubscription($topicName, $subscriptionName)
    {
        try 
        {
            $this->restProxy->deleteSubscription($topicName, $subscriptionName);
        }
        catch (\Exception $e)
        {
            error_log($e->getMessage());
        }
    }

    public function safeDeleteTopic($topicName)
    {
        try 
        {
            $this->restProxy->deleteTopic($topicName);
        }
        catch (\Exception $e)
        {
            error_log($e->getMessage());
        } 
    }
    
    protected function tearDown()
    {
        parent::tearDown();

        foreach ($this->_createdRules as $topicSubscriptionRuleName) {
            $topicSubscriptionRuleNameArray = explode('::', $topicSubscriptionRuleName);
            $topicName = $topicSubscriptionRuleNameArray[0];
            $subscriptionName = $topicSubscriptionRuleNameArray[1];
            $ruleName = $topicSubscriptionRuleNameArray[2];
            $this->safeDeleteRule($topicName, $subscriptionName, $ruleName);
        }
        
        foreach ($this->_createdSubscriptions as $topicSubscriptionName) {
            $topicSubscriptionNameArray = explode('::', $topicSubscriptionName);
            $topicName = $topicSubscriptionNameArray[0];
            $subscriptionName = $topicSubscriptionNameArray[1];
            $this->safeDeleteSubscription($topicName, $subscriptionName);
        } 

        foreach ($this->_createdTopics as $topicName) {
            $this->safeDeleteTopic($topicName);
        }

        foreach ($this->_createdQueues as $queueName) {
            $this->safeDeleteQueue($queueName);
        } 
    }
}

?>
