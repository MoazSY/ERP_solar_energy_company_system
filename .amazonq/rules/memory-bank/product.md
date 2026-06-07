# Product Overview

## Project Purpose
ERP system for solar energy companies operating in Syria. Manages the full lifecycle of solar system sales, installation, delivery, inventory, and maintenance — connecting solar companies, distribution agencies, field employees, and end customers through a single API platform.

## Key Features & Capabilities

### Multi-Actor Platform
- **System Admin**: Platform governance — approves company/agency registrations, manages subscription policies, geographic data
- **Solar Company Manager**: Manages company operations — offers, invoices, installation tasks, employee assignments, delivery, warranties, portfolio
- **Agency Manager**: Manages product distribution — inventory, purchase invoices, delivery tasks, custom discounts per company
- **Employee**: Role-based field operations — drivers (deliveries), inventory managers (stock), install/metal-base technicians (installation tasks)
- **Customer**: End-user facing — browse offers, subscribe, request solar systems, order products, track installation, request maintenance

### Core Business Workflows
1. **Company/Agency Onboarding**: Registration → Admin approval → Subscription to platform policy → Active operations
2. **Product Sales**: Customer browses offers/products → subscribes or places order → invoice generated → delivery assigned → technician installs
3. **Installation Management**: Task assignment to technicians → task acceptance/rejection → solar system definition → system attachment recording → completion
4. **Inventory Control**: Products tracked per company/agency, input/output requests, conflict invoices, low-stock alerts
5. **Payment Handling**: Multi-gateway payment transactions (cash, Syriatel Cash, Shamcash), manager-to-employee cash payments, commission tracking
6. **Maintenance**: Customers request maintenance → company processes → technician dispatched
7. **Warranty Management**: Project-level and component-level warranties recorded post-installation

### Geographic Coverage
- Hierarchical location model: Governorates → Areas → Neighborhoods → Addresses
- Delivery rules scoped per entity and geographic zone
- OSRM integration for route/distance calculations

### External Integrations
- **ApiSyria**: Payment gateway (Syriatel Cash, Shamcash) — balance queries, transaction lookup, payment confirmation
- **OSRM**: Open Source Routing Machine for delivery distance/route calculation

## Target Users
- Solar energy installation companies (B2B)
- Product distribution agencies supplying solar companies
- Field technicians and drivers employed by those entities
- Syrian residential/commercial customers seeking solar system installation or products
