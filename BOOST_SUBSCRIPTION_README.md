# Boost Subscription System

This document provides a comprehensive overview of the boost subscription system implementation for the property platform.

## Overview

The boost subscription system allows users to subscribe to different plans that provide enhanced features like increased listing limits and free viewing requests. The system supports different pricing for various account types and integrates with Flutterwave for payment processing.

## Database Structure

### Tables Created

1. **boost_plans** - Stores available subscription plans
2. **boost_plan_pricing** - Stores pricing variations for different account types
3. **user_boost_subscriptions** - Tracks user subscriptions
4. **user_viewing_requests** - Tracks viewing requests and quota usage

### Migration Files

- `2024_12_19_000001_create_boost_plans_table.php`
- `2024_12_19_000003_create_boost_plan_pricing_table.php`
- `2024_12_19_000004_create_user_boost_subscriptions_table.php`
- `2024_12_19_000005_create_user_viewing_requests_table.php`

## Models

### BoostPlan
**Location:** `app/Models/BoostPlan.php`

**Key Features:**
- Manages boost subscription plans
- Calculates pricing based on account type and billing cycle
- Supports quarterly and yearly discounts
- Includes scopes for active and ordered plans

**Key Methods:**
- `getPriceForAccountType($accountType, $billingCycle)` - Calculates final price
- `active()` - Scope for active plans
- `ordered()` - Scope for ordered plans

### BoostPlanPricing
**Location:** `app/Models/BoostPlanPricing.php`

**Key Features:**
- Manages different pricing for account types
- Supports price multipliers and custom pricing
- Links to boost plans

### UserBoostSubscription
**Location:** `app/Models/UserBoostSubscription.php`

**Key Features:**
- Tracks user subscriptions to boost plans
- Manages subscription lifecycle (pending, active, expired, cancelled)
- Supports auto-renewal functionality
- Integrates with Flutterwave transaction tracking

**Key Methods:**
- `isActive()` - Check if subscription is currently active
- `isExpired()` - Check if subscription has expired
- `getRemainingDays()` - Get days remaining in subscription
- `getNextBillingDate()` - Calculate next billing date

### UserViewingRequest
**Location:** `app/Models/UserViewingRequest.php`

**Key Features:**
- Tracks viewing requests (free and paid)
- Manages monthly quota tracking
- Links to users and properties

**Key Methods:**
- `getFreeRequestsCountForMonth($userId, $year, $month)` - Count free requests
- `hasExceededFreeQuota($userId, $limit, $year, $month)` - Check quota status

## Services

### FlutterwaveService
**Location:** `app/Services/FlutterwaveService.php`

**Key Features:**
- Handles Flutterwave payment integration
- Initializes payments and verifies transactions
- Generates unique transaction references
- Calculates fees and total amounts

**Key Methods:**
- `initializePayment($data)` - Initialize payment with Flutterwave
- `verifyPayment($transactionId)` - Verify payment status
- `getTransactionDetails($transactionId)` - Get transaction details

### BoostSubscriptionService
**Location:** `app/Services/BoostSubscriptionService.php`

**Key Features:**
- Core business logic for boost subscriptions
- Manages subscription lifecycle
- Handles quota management for viewing requests
- Integrates payment processing

**Key Methods:**
- `getAvailablePlans($user)` - Get plans available to user
- `getCurrentSubscription($user)` - Get user's active subscription
- `initializeSubscriptionPayment()` - Start subscription payment process
- `verifyAndActivateSubscription()` - Complete subscription after payment
- `canMakeFreeViewingRequest($user)` - Check viewing quota
- `createViewingRequest()` - Create viewing request

## API Controller

### BoostSubscriptionController
**Location:** `app/Http/Controllers/Api/BoostSubscriptionController.php`

**Endpoints:**

#### GET `/api/boost-subscriptions/plans`
- Returns available boost plans for the authenticated user
- Includes pricing for user's account type

#### GET `/api/boost-subscriptions/current`
- Returns user's current active subscription
- Includes plan details and subscription status

#### GET `/api/boost-subscriptions/history`
- Returns paginated subscription history

#### POST `/api/boost-subscriptions/initialize-payment`
- Initializes payment for a boost subscription
- **Parameters:**
  - `boost_plan_id` (required)
  - `billing_cycle` (required): monthly, quarterly, yearly
  - `redirect_url` (required)

#### POST `/api/boost-subscriptions/verify-payment`
- Verifies payment and activates subscription
- **Parameters:**
  - `transaction_id` (required)

#### PATCH `/api/boost-subscriptions/cancel-auto-renewal`
- Cancels auto-renewal for current subscription

#### PATCH `/api/boost-subscriptions/enable-auto-renewal`
- Enables auto-renewal for current subscription

#### GET `/api/boost-subscriptions/viewing-quota`
- Returns viewing request quota information

#### POST `/api/boost-subscriptions/viewing-request`
- Creates a viewing request
- **Parameters:**
  - `property_id` (required)
  - `force_paid` (optional): boolean

## Data Seeder

### BoostPlanSeeder
**Location:** `database/seeders/BoostPlanSeeder.php`

**Sample Plans:**
1. **Basic Boost** - 5 listings, 3 free viewing requests
2. **Professional Boost** - 15 listings, 10 free viewing requests (Recommended)
3. **Enterprise Boost** - 50 listings, 25 free viewing requests
4. **Premium Boost** - 100 listings, 50 free viewing requests

**Account Type Pricing:**
- `ROLE_USER` - Base pricing (1.0x multiplier)
- `ROLE_BROKER` - 10-25% discount
- `ROLE_DEVELOPER` - 20-35% discount
- `ROLE_COMPANY` - 30-45% discount + custom pricing

## Installation & Setup

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed Data
```bash
php artisan db:seed --class=BoostPlanSeeder
```

### 3. Environment Configuration
Add Flutterwave configuration to your `.env` file:
```env
FLUTTERWAVE_PUBLIC_KEY=your_public_key
FLUTTERWAVE_SECRET_KEY=your_secret_key
FLUTTERWAVE_ENCRYPTION_KEY=your_encryption_key
FLUTTERWAVE_ENVIRONMENT=sandbox # or live
```

### 4. Service Provider Registration
Ensure the services are properly registered in your service container if needed.

## Usage Examples

### Role-Based Pricing

The system handles different role combinations for users:

- **Brokers**: Users with roles `["ROLE_USER","ROLE_BROKERAGE","ROLE_BROKER"]` will get broker pricing
- **Companies**: Users with roles `["ROLE_USER", "ROLE_COMPANY"]` will get company pricing
- **Developers**: Users with roles `["ROLE_USER","ROLE_DEVELOPER"]` will get developer pricing
- **Regular Users**: Users with only `["ROLE_USER"]` will get standard pricing

### Frontend Integration

#### Get Available Plans
```javascript
fetch('/api/boost-subscriptions/plans', {
    headers: {
        'Authorization': 'Bearer ' + token,
        'Accept': 'application/json'
    }
})
.then(response => response.json())
.then(data => {
    console.log('Available plans:', data.data);
});
```

#### Initialize Payment
```javascript
fetch('/api/boost-subscriptions/initialize-payment', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    body: JSON.stringify({
        boost_plan_id: 1,
        billing_cycle: 'monthly',
        redirect_url: 'https://yoursite.com/payment-callback'
    })
})
.then(response => response.json())
.then(data => {
    if (data.status === 'success') {
        // Redirect to payment link
        window.location.href = data.data.payment_link;
    }
});
```

#### Check Viewing Quota
```javascript
fetch('/api/boost-subscriptions/viewing-quota', {
    headers: {
        'Authorization': 'Bearer ' + token,
        'Accept': 'application/json'
    }
})
.then(response => response.json())
.then(data => {
    const quota = data.data;
    console.log(`Used: ${quota.free_requests_used}/${quota.free_requests_limit}`);
    console.log('Can make free request:', quota.can_request);
});
```

## Security Considerations

1. **API Authentication**: All endpoints require authentication via Sanctum
2. **Input Validation**: All inputs are validated using Laravel's validation rules
3. **Database Transactions**: Critical operations use database transactions
4. **Payment Security**: Flutterwave handles sensitive payment data
5. **Error Handling**: Comprehensive error handling with appropriate HTTP status codes

## Testing

### Unit Tests
Create tests for:
- Model relationships and methods
- Service class methods
- Payment processing logic
- Quota calculations

### Integration Tests
Create tests for:
- API endpoints
- Payment flow
- Subscription lifecycle
- Quota management

## Monitoring & Maintenance

### Key Metrics to Monitor
1. Subscription conversion rates
2. Payment success/failure rates
3. Quota usage patterns
4. Plan popularity
5. Revenue metrics

### Regular Maintenance Tasks
1. Clean up expired subscriptions
2. Process auto-renewals
3. Monitor payment failures
4. Update pricing as needed
5. Review and optimize database queries

## Future Enhancements

1. **Promo Codes**: Add support for discount codes
2. **Plan Upgrades/Downgrades**: Allow mid-cycle plan changes
3. **Usage Analytics**: Detailed usage tracking and reporting
4. **Notification System**: Email/SMS notifications for subscription events
5. **Admin Dashboard**: Management interface for plans and subscriptions
6. **API Rate Limiting**: Implement rate limiting based on subscription tiers
7. **Webhook Integration**: Handle Flutterwave webhooks for real-time updates

## Support

For issues or questions regarding the boost subscription system:
1. Check the error logs for detailed error messages
2. Verify Flutterwave configuration and credentials
3. Ensure all migrations have been run
4. Check API authentication and permissions
5. Review database constraints and relationships