<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Sales;

use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for credit memo functionality
 */
class CreditmemoTest extends GraphQlAbstract
{
    /**
     * @var GetCustomerAuthenticationHeader
     */
    private $customerAuthenticationHeader;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->customerAuthenticationHeader = Bootstrap::getObjectManager()->get(
            GetCustomerAuthenticationHeader::class
        );
    }

    /**
     * @magentoApiDataFixture Magento/Sales/_files/customer_creditmemo_with_two_items.php
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreditMemoForLoggedInCustomerQuery(): void
    {
        $query =
            <<<QUERY
query {
  customer {
    orders {
        items {
            credit_memos {
                items {
                    product_name
                    product_sku
                    product_sale_price {
                        value
                    }
                    quantity_refunded
                }
                total {
                    subtotal {
                        value
                    }
                    grand_total {
                        value
                    }
                    shipping_amount {
                        value
                    }
                    adjustment {
                        value
                    }
                }
            }
        }
    }
  }
}
QUERY;

        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $this->customerAuthenticationHeader->execute($currentEmail, $currentPassword)
        );

        $expectedCreditMemoData = [
            [
                'items' => [
                    [
                        'product_name' => 'Simple Related Product',
                        'product_sku' => 'simple',
                        'product_sale_price' => [
                            'value' => 10
                        ],
                        'quantity_refunded' => 1
                    ],
                    [
                        'product_name' => 'Simple Product With Related Product',
                        'product_sku' => 'simple_with_cross',
                        'product_sale_price' => [
                            'value' => 10
                        ],
                        'quantity_refunded' => 1
                    ]
                ],
                'total' => [
                    'subtotal' => [
                        'value' => 20
                    ],
                    'grand_total' => [
                        'value' => 20
                    ],
                    'shipping_amount' => [
                        'value' => 0
                    ],
                    'adjustment' => [
                        'value' => 1.23
                    ]
                ]
            ]
        ];

        $actualData = $response['customer']['orders']['items'][1];

        $creditMemos = $actualData['credit_memos'];
        $this->assertResponseFields($creditMemos, $expectedCreditMemoData);
    }
}
