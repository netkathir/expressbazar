# Multi-Vendor Ecommerce System

Laravel 12 + MySQL + Stripe + EposNow

## Project Overview

This project is a scalable multi-vendor ecommerce platform built with Laravel 12 and MySQL. It combines marketplace functionality, location-aware vendor discovery, online payments, inventory synchronization, and role-based control panels for admins, vendors, and customers.

## Core Goals

- Support multiple vendors in a single marketplace
- Filter vendors by customer location
- Sync inventory with EposNow
- Enable secure payments through Stripe
- Provide offer, discount, and coupon workflows
- Offer dedicated admin, vendor, and customer panels

## Architecture

- MVC for application structure
- Service layer for business rules
- Repository pattern for database abstraction
- RESTful APIs for frontend and integrations
- Queue and scheduler for background jobs and sync tasks

## User Roles

| Role | Access |
| --- | --- |
| Admin | Full system control |
| Vendor | Manage products, stock, and orders |
| Customer | Browse products, place orders, and manage profile |

## Database Design

### Core Tables

- `users`
- `vendors`
- `products`
- `categories`
- `product_category`
- `carts`
- `cart_items`
- `orders`
- `order_items`
- `payments`

### Discount Tables

- `coupons`
- `discounts`
- `coupon_usages`

## Location-Based Filtering

The system detects customer location using:

- Latitude and longitude
- Pincode

Nearby vendors are found using the Haversine formula and filtered by radius, such as 20 km.

## Ecommerce Flow

1. Customer enters location
2. System loads nearby vendors
3. Customer selects a vendor
4. Products are displayed
5. Items are added to cart
6. Stock is validated
7. Discounts are applied
8. Checkout is completed
9. Payment is processed through Stripe
10. Order is created
11. Inventory is updated

## Payment Integration

Stripe is used for online payments.

### Payment Flow

- Create a PaymentIntent
- Send `client_secret` to the frontend
- Confirm payment
- Store transaction details

### Payment Status

- Pending
- Paid
- Failed

## EposNow Integration

EposNow is used for inventory synchronization.

### Features

- Fetch product stock
- Sync inventory data

### Implementation

- Service class: `EposNowService`
- Scheduled sync every 10 minutes

## Inventory Management

Inventory tracking includes:

- `stock_quantity`
- `last_synced_at`

### Inventory Flow

- Validate stock before checkout
- Deduct stock after order placement
- Sync stock with EposNow on schedule

## Offers and Discounts

### Discount Types

- Product-level discounts
- Vendor-level discounts
- Cart-level discounts
- Coupon-based discounts

### Discount Order

1. Product discount
2. Vendor discount
3. Cart discount
4. Coupon discount

### Example

- `₹1000` with 10% product discount becomes `₹900`
- Vendor discount of `₹50` becomes `₹850`
- Cart discount of 5% becomes `₹807.5`
- Coupon discount reduces the total to `₹727.5`

## Core Services

- `EposNowService`
- `StripeService`
- `DiscountService`
- `CartService`
- `OrderService`

## Admin Panel

### Features

- Dashboard for users, revenue, and orders
- Manage users
- Manage vendors
- Manage products
- Manage categories
- Manage orders
- Monitor payments
- Manage offers and coupons
- Monitor inventory

## Vendor Panel

- Sales and orders dashboard
- Manage products
- View stock
- Manage orders
- Create product-level discounts

## Customer Panel

- Location selection
- Vendor selection
- Product browsing
- Cart management
- Checkout
- Payment
- Order history
- Profile management

## API Structure

```bash
GET    /api/vendors?lat=&lng=
GET    /api/products?vendor_id=
POST   /api/cart/add
POST   /api/checkout
POST   /api/payment/intent
POST   /api/payment/confirm
POST   /api/coupon/apply
GET    /api/epos/stock/{product_id}
```

## Frontend Pages

- Home with location and vendor discovery
- Product listing
- Cart
- Checkout
- Payment success and failure

## Performance Considerations

- Cache vendors and products
- Use Redis where appropriate
- Queue external API calls
- Avoid real-time EposNow requests during checkout

## Security

- Validate all inputs
- Store API keys in `.env`
- Use policies and middleware
- Never trust frontend pricing
- Always calculate totals on the backend

## Routing Structure

```bash
/admin/*
/vendor/*
/user/*
```

## Offers UI

- Show discount badges
- Include coupon input at checkout
- Display a transparent price breakdown

## Bonus Features

- Google Maps vendor display
- Distance display
- Ratings and reviews
- Wishlist
- First-order discount
- Festival offers

## Critical Best Practices

- Never trust frontend pricing
- Always calculate prices on the backend
- Cache inventory data
- Use retry logic for EposNow
- Keep business logic inside services

## Final Result

### Customer Flow

Browse -> Select Vendor -> Add to Cart -> Apply Offers -> Pay -> Order

### Admin Flow

Full system control

### Vendor Flow

Manage products and fulfill orders

## Next Steps

If you want to continue, the next practical deliverables are:

- Full Laravel project structure
- Controllers and service classes
- Database ER diagram
- Stripe and EposNow implementation
- Production deployment guide
