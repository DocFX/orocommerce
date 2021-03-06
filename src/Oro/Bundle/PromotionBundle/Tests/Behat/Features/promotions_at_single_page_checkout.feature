@ticket-BB-16591
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:promotions.yml
@fixture-OroPromotionBundle:shopping_list.yml
Feature: Promotions at single page checkout
  In order to find out applied discounts at checkout
  As an site user
  I need to have ability to see applied discounts at checkout stage on front-end

  Scenario: Check line item and order discount at Billing Information Checkout's step
    Given I login as administrator
    And I activate "Single Page Checkout" workflow
    And I disable inventory management
    Then I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    Then I see next line item discounts for checkout:
      | SKU  | Discount |
      | SKU1 |          |
      | SKU2 | -$5.00   |
    And I see next subtotals for "Single Page Checkout Step":
      | Subtotal          | Amount  |
      | Discount          | -$12.50 |
      | Shipping Discount | -$1.00  |
    Then I click "Submit Order"
    And Email should contains the following:
      | Body | Discount -$12.50         |
      | Body | Shipping Discount -$1.00 |
    And I follow "click here to review"

  Scenario: Check line item and order discount at Order View page
    Given I should be on Order Frontend View page
    And I show column "Row Total (Discount Amount)" in "Order Line Items Grid" frontend grid
    Then I see next line item discounts for order:
      | SKU  | Row Total (Discount Amount) |
      | SKU1 | $0.00                       |
      | SKU2 | $5.00                       |
    And I see next subtotals for "Order":
      | Subtotal          | Amount  |
      | Discount          | -$12.50 |
      | Shipping Discount | -$1.00  |
