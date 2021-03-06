<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData as OroLoadCustomerUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class OpenOrdersControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(OroLoadCustomerUserData::AUTH_USER, OroLoadCustomerUserData::AUTH_PW)
        );
    }

    public function testOpenOrdersWhenConfigIsOff()
    {
        $website = self::getContainer()->get('oro_website.manager')->getDefaultWebsite();
        self::getContainer()->get('oro_config.manager')->set('oro_checkout.frontend_show_open_orders', false, $website);
        $this->client->request('GET', $this->getUrl('oro_checkout_frontend_open_orders'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 404);
        self::getContainer()->get('oro_config.manager')->set('oro_checkout.frontend_show_open_orders', true, $website);
    }

    public function testOpenOrders()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_checkout_frontend_open_orders'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('Open Orders', $crawler->filter('h1.page-title')->html());
        static::assertStringContainsString('grid-frontend-checkouts-grid', $crawler->html());
    }

    public function testOpenOrdersIfSeparatePageSettingIsTrue()
    {
        $configManager = $this
            ->getContainer()
            ->get('oro_config.manager');

        $configManager->set('oro_checkout.frontend_open_orders_separate_page', true);
        $configManager->flush();

        $crawler = $this->client->request('GET', $this->getUrl('oro_order_frontend_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertStringNotContainsString('grid-frontend-checkouts-grid', $crawler->html());

        $navigationList = $crawler->filter('ul.primary-menu');

        static::assertStringContainsString('Open Orders', $navigationList->html());
    }

    public function testOpenOrdersIfSeparatePageSettingIsFalse()
    {
        $configManager = $this
            ->getContainer()
            ->get('oro_config.manager');

        $configManager->set('oro_checkout.frontend_open_orders_separate_page', false);
        $configManager->flush();

        $crawler = $this->client->request('GET', $this->getUrl('oro_order_frontend_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertStringContainsString('grid-frontend-checkouts-grid', $crawler->html());

        $navigationList = $crawler->filter('ul.primary-menu');

        static::assertStringNotContainsString('Open Orders', $navigationList->html());
    }
}
