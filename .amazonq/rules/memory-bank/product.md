# Solar Company ERP - Product Overview

## Project Purpose
A full-featured ERP (Enterprise Resource Planning) REST API backend for managing solar energy company operations in Syria. Built with Laravel 12, it handles the complete lifecycle from customer acquisition to solar system installation, inventory management, and post-sale services.

## Value Proposition
- Centralizes operations for solar energy companies and their distribution agencies
- Automates order management, invoicing, delivery, and installation workflows
- Provides multi-role access control for different stakeholders
- Integrates Syrian payment systems (ShamCash, SyriaTel Cash)
- Tracks warranties, maintenance, and financial performance

## Key Features & Capabilities

### Multi-Actor Platform
- **System Admin**: Platform-wide administration, subscription policy management, geographic data
- **Solar Company Manager**: Company registration, product ordering from agencies, employee management, invoice creation, installation task assignment
- **Agency Manager**: Product catalog management, custom discounts per company, delivery task assignment, purchase invoice creation
- **Employee (Technician/Driver/Inventory)**: Delivery task processing, installation tasks (accept/reject/start/complete), solar system definition for customers, inventory management
- **Customer**: Solar system requests, offer subscriptions, order placement, invoice approval, maintenance requests, company ratings

### Core Business Modules
- **Authentication**: OTP-based registration and login with multi-guard Sanctum tokens + refresh token rotation
- **Geographic Management**: Governorates → Areas → Neighborhoods hierarchy
- **Product Catalog**: Products with sub-types (batteries, inverters, solar panels) with stock tracking
- **Order Management**: Customer order lists → agency purchase invoices → delivery tasks
- **Installation Workflow**: Task assignment → technician acceptance → solar system definition → task completion with images
- **Inventory Management**: Stock input/output requests, conflict invoices, low-stock alerts
- **Financial Tracking**: Installation profits, commission policies, payment transactions, inner sales
- **Subscription System**: Company/agency subscription to service policies with custom plans
- **Warranty Management**: Project and component warranties
- **Maintenance Services**: Customer maintenance requests with cost tracking
- **Portfolio**: Company project portfolios with media
- **Ratings & Feedback**: Customer ratings for companies, agencies, and technicians
- **Financial Simulation**: Solar system financial savings calculator

## Target Users
- Solar energy installation companies in Syria
- Product distribution agencies
- Company employees (technicians, drivers, inventory managers)
- End customers seeking solar energy solutions

## Business Context
- Operates within the Syrian market (Syrian phone number format: `09XXXXXXXX`)
- Supports Syrian payment gateways: ShamCash and SyriaTel Cash
- Geographic data scoped to Syrian governorates and areas
- Prices and financial calculations relevant to local solar market
