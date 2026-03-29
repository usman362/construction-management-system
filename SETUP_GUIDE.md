# Construction Management System - Setup & User Guide

## System Requirements

- **PHP**: 8.2 or higher
- **Database**: MySQL 8.0+ or MariaDB 10.6+
- **Package Manager**: Composer 2.x
- **Node.js**: 18+ (optional, for Vite asset compilation)
- **Web Server**: Apache or Nginx (or use PHP's built-in server for development)

## Quick Start

### 1. Clone or Copy the Project

```bash
# If cloning from a repository
git clone <repository-url> construction-management-system
cd construction-management-system
```

### 2. Configure Environment Variables

Copy the example environment file to create your local configuration:

```bash
cp .env.example .env
```

Or if using an existing `.env` file, verify the configuration.

### 3. Configure Database Settings

Edit the `.env` file and set your database credentials:

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=construction_management
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 4. Create the Database

```bash
mysql -u root -p -e "CREATE DATABASE construction_management;"
```

You will be prompted for your MySQL root password.

### 5. Install Dependencies

```bash
composer install
```

### 6. Generate Application Key

```bash
php artisan key:generate
```

This generates the `APP_KEY` in your `.env` file, required for encryption.

### 7. Run Database Migrations

```bash
php artisan migrate
```

This creates all 31 tables in the database schema.

### 8. Seed Sample Data (Optional)

```bash
php artisan db:seed
```

This populates the database with sample projects, employees, timesheets, and change orders for testing and demonstration purposes.

### 9. Start the Development Server

```bash
php artisan serve
```

The server will start on `http://localhost:8000` by default.

### 10. Access the Application

Open your web browser and navigate to:

```
http://localhost:8000
```

**Default Login Credentials** (after seeding):
- **Email**: admin@cms.com
- **Password**: password

---

## Database Architecture

The Construction Management System uses 31 tables organized by functional module. All financial amounts use `decimal(15,2)` precision for accurate accounting.

### Core Tables
- **users**: System users with roles and permissions
- **clients**: Client/customer information
- **employees**: Workforce with craft assignments, hourly and billable rates
- **crafts**: Trade/skill categories (e.g., Electrician, Carpenter, Laborer)
- **shifts**: Work shift definitions (e.g., Day, Night, Swing)

### Project Management Tables
- **projects**: Construction projects/jobs with budget and status tracking
- **project_phases**: Project phases/stages for workflow management
- **cost_codes**: Hierarchical cost categorization (01-Labor, 02-Material, 03-Rental, etc.)
- **budget_lines**: Budget allocation by cost code per project

### Workforce Management Tables
- **crews**: Crew groupings with foreman and shift assignment
- **crew_members**: Mapping of employees to crews

### Time Tracking Tables
- **timesheets**: Daily time entry records by employee and project
- **timesheet_cost_allocations**: Cost code allocation per timesheet entry

### Financial Tables
- **commitments**: Purchase orders and vendor commitments
- **invoices**: Vendor invoice records and approval tracking
- **cost_entries**: Detailed cost transactions and adjustments

### Change Order Tables
- **change_orders**: Change order records with status and revision tracking
- **change_order_items**: Material and equipment items in change orders
- **change_order_labor**: Labor costs and breakdowns in change orders

### Equipment & Materials Tables
- **equipment**: Equipment inventory (owned and rented) with rates
- **equipment_assignments**: Assignment of equipment to projects
- **materials**: Material inventory and specifications
- **material_usages**: Usage tracking and costs

### Estimation Tables
- **estimates**: Project estimates with line items
- **estimate_lines**: Individual estimate line items by cost code

### Payroll & Billing Tables
- **payroll_periods**: Payroll period definitions and statuses
- **payroll_entries**: Individual payroll entries generated from timesheets
- **billing_invoices**: Client billing invoices generated from project costs

### Additional Tables
- **manhour_budgets**: Budgeted hours by craft per project
- **per_diem_rates**: Daily allowance rates by location/craft
- **daily_logs**: Daily project logs and notes

---

## Module Overview

### Dashboard

The Dashboard provides a high-level overview of your construction business:
- **Active Projects**: Quick view of ongoing jobs with current status
- **Employees**: Summary of workforce availability and assignments
- **Pending Items**: Important tasks requiring attention (pending timesheets, approvals, etc.)
- **Quick Actions**: Shortcuts to common operations

### Projects

Manage your construction projects/jobs:
- **Create Project**: Set up a new job with name, client, budget, and timeline
- **Track Costs**: Monitor actual costs against budget
- **Manage Phases**: Break projects into manageable phases/stages
- **Budget Management**: Set initial budget and track revisions through change orders
- **Project Settings**: Configure cost codes, budget lines, and project-specific settings

### Employees

Manage your workforce:
- **Employee Records**: Add/edit employee information, contact details, and employment status
- **Craft Assignment**: Assign trade skills (Electrician, Carpenter, etc.) to employees
- **Hourly Rates**: Set labor rates for payroll calculations
- **Billable Rates**: Set rates to charge clients for labor
- **Availability**: Track employment status and shift assignments

### Crews

Organize workers into managed crews:
- **Create Crew**: Define a crew with name and description
- **Assign Members**: Add employees to crews for coordinated work
- **Foreman Assignment**: Designate a crew foreman/leader
- **Shift Configuration**: Assign crews to specific work shifts
- **Crew Tracking**: Monitor crew productivity and utilization

### Timesheets

Track daily work hours with support for individual and bulk crew entry:

#### Individual Entry
- **Daily Time Entry**: Record hours by employee, project, and cost code
- **Multiple Projects**: Allocate employee hours across different projects in one day
- **Cost Code Allocation**: Distribute hours among relevant cost codes

#### Crew (Bulk) Entry
- **Crew-Based Entry**: Record hours for entire crew at once
- **Efficient Data Entry**: Faster input for crews working on same project

#### Timesheet Workflow
- **Draft Status**: Initial entry of timesheet data
- **Pending Approval**: Waiting for supervisor/manager review
- **Approved Status**: Finalized and ready for payroll and billing
- **Archived**: Historical records

#### Features
- **Flexibility**: Edit draft and approved timesheets (with tracking)
- **Validation**: Ensure hours align with shifts and budgets
- **Cost Code Integration**: Automatic cost classification

### Cost Codes

Hierarchical cost categorization following construction accounting standards:

- **01 - Labor**: Direct labor costs by trade/craft
- **02 - Material**: Materials and supplies
- **03 - Rental**: Equipment rental costs
- **04 - Equipment**: Owned equipment depreciation
- **05 - Subcontract**: Subcontractor costs
- **06 - Other**: Miscellaneous costs

**Features**:
- **Hierarchical Structure**: Parent and child codes for detailed categorization
- **Industry Standard**: Aligns with standard construction accounting practices
- **Flexible**: Customize codes per project or company-wide

### Budget

Plan and track project spending:

- **Initial Budget**: Set overall project budget by cost code
- **Budget Lines**: Allocate budget to specific cost codes
- **Revised Budget**: Adjust budget through change order approvals
- **Budget vs. Actual**: Compare planned vs. actual spending
- **Variance Analysis**: Identify over/under budget areas

**Typical Workflow**:
1. Create project with initial budget estimate
2. Break down budget into cost code line items
3. Allocate percentage or dollar amount per cost code
4. Track actuals (timesheets, commitments, invoices)
5. Adjust budget with approved change orders

### Commitments

Track purchase orders and vendor commitments:

- **Create Commitment**: Record purchase orders to vendors
- **Vendor Assignment**: Link to vendor/supplier records
- **Item Tracking**: Record committed materials, equipment, services
- **Cost Tracking**: Record committed dollar amounts
- **Status Management**: Track commitment status from creation to fulfillment

**Integration**:
- Links to vendor invoices for matching
- Prevents over-commitment against budget
- Supports commitment vs. actual variance analysis

### Vendor Invoices

Record and manage vendor invoicing:

- **Invoice Entry**: Record invoices from vendors/subcontractors
- **Commitment Matching**: Match invoices to original purchase orders
- **Approval Workflow**: Route invoices for approval
- **Payment Tracking**: Track payment status and dates
- **Cost Integration**: Post costs to projects for accurate financials

**Workflow**:
1. Create commitment (PO) for vendor work
2. Receive invoice from vendor
3. Match invoice to commitment
4. Route for approval
5. Post to project costs

### Change Orders

Manage project scope changes and cost impacts:

- **Create Change Order**: Document scope changes with cost impact
- **Labor Breakdown**: Detail labor hours, rates, and classifications
- **Material Items**: List materials added/removed with costs
- **Equipment Costs**: Include equipment rental/purchase impact
- **Client Format**: Matches Change Order Directive format expected by clients
- **Revision Tracking**: Manage multiple revisions of each change order
- **Approval Status**: Track change order approval through workflow

**Workflow**:
1. Identify scope change
2. Calculate labor, material, and equipment impact
3. Create change order with detailed breakdown
4. Obtain client/PM approval
5. Update project budget with approved amount
6. Execute change order work

**Data Tracked**:
- Summary description of change
- Labor costs (hours × rate by classification)
- Material costs (items and quantities)
- Equipment costs (rental adjustments)
- Total cost impact (positive or negative)
- Revision history and approval dates

### Estimates

Create project estimates before work begins:

- **Create Estimate**: Develop estimate for new project
- **Line Items**: Build estimate with detailed cost code line items
- **Cost Breakdown**: Estimate costs for labor, materials, equipment, subcontract
- **Markup**: Apply markup percentages for profit
- **Revision**: Update estimates as scope clarifies
- **Quote Format**: Generate formal quote documents for clients

**Use Cases**:
- Bid preparation
- Initial project budgeting
- Change order estimating
- Client quote submission

### Vendors

Manage relationships with external suppliers:

- **Vendor Records**: Maintain vendor contact information
- **Vendor Types**: Categorize as suppliers, subcontractors, equipment rentals
- **Contact Details**: Phone, email, address information
- **Payment Terms**: Track preferred payment terms
- **Performance History**: View commitment and invoice history

### Equipment

Track equipment assets:

- **Equipment Inventory**: Catalog equipment (owned and rented)
- **Equipment Type**: Classify by type (excavator, crane, etc.)
- **Rates**: Set hourly/daily rental rates
- **Assignment**: Assign equipment to projects with date ranges
- **Utilization**: Track equipment usage and costs
- **Maintenance**: Track maintenance history and costs

**Features**:
- **Owned vs. Rented**: Distinguish between company-owned and rented equipment
- **Cost Tracking**: Monitor equipment expenses per project
- **Availability**: Track equipment availability and scheduling

### Materials

Manage material inventory and usage:

- **Material Records**: Define materials used in projects
- **Specification**: Include unit, description, and cost
- **Usage Tracking**: Record material consumption per project
- **Cost Impact**: Track material costs to projects via timesheets and invoices
- **Inventory**: Basic inventory level tracking

**Features**:
- **Material Library**: Build standard material catalog
- **Cost Per Unit**: Maintain accurate pricing
- **Usage Reports**: Track material consumption patterns

### Payroll

Generate payroll from approved timesheets:

- **Payroll Periods**: Define payroll periods (weekly, bi-weekly, monthly)
- **Create Payroll**: Generate payroll from approved timesheets for a period
- **Employee Allocation**: Automatically allocate employees' hours to payroll
- **Rate Application**: Apply hourly rates and apply any adjustments
- **Gross Pay Calculation**: Calculate gross pay, deductions, net pay
- **Payroll Export**: Export for payroll processing/ADP integration

**Workflow**:
1. Define payroll period (week ending date)
2. Ensure all timesheets for period are approved
3. Create payroll for period
4. Review calculated amounts
5. Export for payroll service or manual processing
6. Mark payroll as processed

### Billing

Generate client invoices from project costs:

- **Create Invoice**: Generate invoice for client for work performed
- **Cost Basis**: Invoice can be based on:
  - Timesheet labor costs (hours × billable rate)
  - Committed costs and invoices
  - Cost codes within project
- **Line Items**: Detailed billing breakdown by cost code
- **Client Format**: Professional invoice format suitable for client submission
- **Payment Tracking**: Track invoice submission and payment status

**Workflow**:
1. Define billing period
2. Select project or cost codes to bill
3. Create billing invoice
4. Review detailed costs
5. Submit to client
6. Track payment receipt

### Reports

Six comprehensive report types provide visibility into project financials and productivity:

#### 1. Cost Report

**Purpose**: Budget vs. Committed vs. Invoiced comparison by cost code

**Data Shown**:
- Cost code and description
- Original budget amount
- Budget remaining (available for commitment)
- Committed amounts (purchase orders)
- Invoiced amounts (received invoices)
- Actual costs (paid/posted)
- Variance (budget - actual)
- Variance percentage

**Use Cases**:
- Monitor budget consumption
- Identify over/under budget areas
- Track spending pace
- Cost control analysis

#### 2. Forecast Report

**Purpose**: Budget vs. Forecast with Change Order adjustments

**Data Shown**:
- Cost code
- Original budget
- Approved change orders (additions/deletions)
- Revised budget (budget + COs)
- Forecasted final cost (estimate of total spend)
- Variance to revised budget
- Expected final profit/loss

**Use Cases**:
- Project financial projections
- Change order impact analysis
- Profit forecasting
- Risk identification

#### 3. Manhour Report

**Purpose**: Track hours worked across employees, crafts, or cost codes

**Data Shown**:
- Grouping options:
  - By Employee: Hours per employee with craft and cost code detail
  - By Craft: Hours by trade with employee listing
  - By Cost Code: Hours by cost code with employee/craft breakdown
- Hours worked
- Hours budgeted
- Hours variance
- Productivity metrics

**Use Cases**:
- Crew productivity analysis
- Craft utilization tracking
- Cost code allocation verification
- Workforce planning

#### 4. Timesheet Report

**Purpose**: Detailed timesheet data with flexible grouping

**Data Shown**:
- Date, employee, project, craft, cost code
- Hours worked
- Hourly rate
- Line item cost
- Approval status
- Bulk entry indicator (crew entry)

**Grouping Options**:
- By Project
- By Employee
- By Craft
- By Cost Code
- By Status (draft/approved)

**Use Cases**:
- Audit timesheet entries
- Verify cost allocations
- Track approval status
- Historical records

#### 5. Profit & Loss Report

**Purpose**: Revenue vs. cost analysis for project profitability

**Data Shown**:
- Cost code breakdown
- Revenue (billable labor × billable rate)
- Labor costs (hours × labor rate)
- Material costs (invoiced commitments)
- Equipment costs (rental/purchase)
- Other costs
- Gross profit
- Profit margin percentage

**Use Cases**:
- Profitability analysis
- Pricing review
- Cost control decisions
- Project performance assessment

#### 6. Productivity Report

**Purpose**: Earned hours vs. actual hours with productivity percentages

**Data Shown**:
- Employee or cost code
- Actual hours worked
- Earned hours (standard hours per unit produced)
- Productivity % (earned ÷ actual)
- Performance variance

**Use Cases**:
- Worker productivity tracking
- Performance management
- Efficiency improvement identification
- Incentive program basis

---

## Typical Workflow

The Construction Management System follows a standard construction job workflow:

### Step 1: Project Setup

1. **Create Project**
   - Enter project name, client, location
   - Set initial budget amount
   - Define project timeline

2. **Configure Cost Codes**
   - Ensure cost codes are set up (or use standard defaults)
   - Map cost codes relevant to project scope

3. **Create Budget Lines**
   - Allocate initial project budget by cost code
   - Set budget percentages or dollar amounts
   - Plan allocation for labor, materials, equipment, subcontract

### Step 2: Workforce Setup

1. **Create/Assign Employees**
   - Ensure employees are entered in system
   - Verify craft assignments
   - Confirm labor and billable rates

2. **Create Crews**
   - Organize employees into crews (if crew-based work)
   - Assign foreman
   - Set crew shift schedule

### Step 3: Daily Work Tracking

1. **Enter Timesheets**
   - Option A: Individual entry by employee
   - Option B: Crew bulk entry for entire crew
   - Allocate hours to cost codes
   - Record by date, employee/crew, project

2. **Approve Timesheets**
   - Supervisor reviews submitted timesheets
   - Verify hours and cost code allocation
   - Approve for payroll and billing

### Step 4: Manage Commitments & Invoices

1. **Track Vendor Commitments**
   - Create purchase orders for materials, equipment, subcontract
   - Record committed costs
   - Track against budget

2. **Record Vendor Invoices**
   - Receive invoices from vendors/subs
   - Match to original commitments
   - Approve for payment

### Step 5: Handle Change Orders

1. **Identify Scope Changes**
   - Document changes requested by client
   - Calculate cost/schedule impact

2. **Create Change Order**
   - Detail labor impacts (hours, rates, classifications)
   - List material additions/deletions
   - Include equipment cost adjustments

3. **Obtain Approvals**
   - Route for PM/client approval
   - Document approval and revision tracking

4. **Execute Change**
   - Update project budget with approved CO amount
   - Track work against change order scope
   - Record costs under change order tracking

### Step 6: Financial Reporting & Analysis

1. **Run Reports**
   - **Cost Report**: Verify budget consumption
   - **Forecast**: Project final cost/profit
   - **Manhour Report**: Analyze labor productivity
   - **Timesheet Report**: Audit time entries
   - **P&L**: Review profitability

2. **Address Variances**
   - Identify areas over budget
   - Adjust scope or staffing as needed
   - Document decisions

### Step 7: Payroll & Billing

1. **Generate Payroll**
   - Define payroll period
   - Create payroll from approved timesheets
   - Review and export for payroll service

2. **Generate Billing**
   - Create client invoice for completed work
   - Include billable labor, materials, equipment
   - Format for client submission
   - Track payment receipt

### Step 8: Close Project (End of Project)

1. **Final Reports**
   - Run final Cost Report vs. Budget
   - Generate final P&L for project
   - Analyze profitability

2. **Archive Records**
   - Archive timesheets and invoices
   - Retain for audit and historical reference

3. **Close Project**
   - Mark project as closed/complete
   - Archive project records

---

## Login Credentials

After running the seeding command (`php artisan db:seed`), the following default credentials are available:

**Email**: `admin@cms.com`
**Password**: `password`

**Security Note**: Change these credentials in a production environment. You can change the password through the user profile settings.

---

## Sample Data

When you run `php artisan db:seed`, the database is populated with comprehensive sample data for testing and demonstration:

### Projects

- **BM-5403 (HI3 Heater Repairs)**
  - Comprehensive project with full cost data
  - Multiple change orders
  - Equipment and material tracking
  - Complete timesheet history

- **GC-7201 (Turnaround Support)**
  - Secondary project for comparison
  - Different work types and cost structures

### Workforce

- **12 Employees** across 6 different crafts:
  - Electrician
  - Carpenter
  - Laborer
  - Welder
  - HVAC Technician
  - Equipment Operator

- **2 Crews** with assigned members and foremen

### Time Tracking

- **30 days of timesheet data** with:
  - Individual and crew entries
  - Multiple cost code allocations
  - Approved and pending records
  - Realistic work patterns

### Cost Tracking

- **Budget lines** set for primary project
- **7 Change Orders** (matching PDF examples) with:
  - Labor breakdowns
  - Material additions
  - Equipment cost impacts
  - Revision tracking
- **Commitments and invoices** for materials and services
- **Equipment assignments** to projects
- **Material usage** records

### Financial Data

All sample data includes realistic costs, rates, and financial relationships for comprehensive testing of reporting and financial features.

---

## Technical Notes

### Frontend & Styling

- **Tailwind CSS**: Styling framework served via CDN for rapid UI development
- **Alpine.js**: Lightweight JavaScript framework for interactive components and dynamic functionality
- **Responsive Design**: Mobile-friendly UI that adapts to different screen sizes

### Reporting

- **HTML Reports**: All reports are rendered as HTML pages
- **Print-Friendly**: Reports can be printed directly from browser
- **Export Options**: Browser print function supports PDF export
- **Dynamic Filtering**: Most reports support filtering by project, date range, or cost code

### Architecture & Configuration

- **Database Precision**: All financial amounts use `decimal(15,2)` data type for accurate accounting and calculations
- **Authentication**: No authentication middleware is enabled by default
  - Add authentication middleware as needed for your environment
  - Consider using Laravel's built-in authentication scaffolding (`php artisan ui bootstrap --auth`)
- **API**: RESTful endpoints support both HTML views and JSON responses
- **Stateless Design**: Application uses standard Laravel request/response lifecycle

### Performance Considerations

- **Database Queries**: Optimized with eager loading to prevent N+1 queries
- **Pagination**: Large datasets (timesheets, invoices) are paginated for performance
- **Caching**: Configure Laravel caching for frequently accessed reference data (cost codes, employees)
- **Indexing**: Database indexes are created on foreign keys and frequently searched columns

### Development & Deployment

- **Environment Configuration**: Use `.env` file for environment-specific settings
- **Migration System**: Use `php artisan migrate` for database versioning
- **Seeding**: Use `php artisan db:seed` for development data
- **Asset Compilation**: Optional Vite compilation for modern JavaScript bundling
- **Logging**: Laravel logging configured for debugging and audit trails

### Common Commands

```bash
# Database operations
php artisan migrate              # Run all migrations
php artisan migrate:rollback     # Rollback last migration batch
php artisan migrate:reset        # Reset all migrations
php artisan db:seed              # Seed sample data

# Cache clearing
php artisan cache:clear          # Clear all caches
php artisan config:cache         # Cache configuration

# Development
php artisan serve                # Start development server
php artisan tinker               # Interactive shell for testing

# Asset compilation (with Node.js)
npm install                      # Install Node dependencies
npm run dev                       # Development build
npm run build                     # Production build
```

---

## Troubleshooting

### Database Connection Error

**Problem**: "Connection refused" when running migrations

**Solution**:
1. Verify MySQL is running: `mysql -u root -p -e "SELECT 1"`
2. Check `.env` database credentials match your MySQL setup
3. Verify database exists: `mysql -u root -p -e "SHOW DATABASES;"`

### Missing APP_KEY

**Problem**: "No application encryption key has been specified"

**Solution**: Run `php artisan key:generate`

### Permission Denied on Storage/Bootstrap

**Problem**: File permission errors

**Solution**:
```bash
chmod -R 775 storage bootstrap/cache
chmod -R 777 storage/logs
```

### Port Already in Use

**Problem**: "Port 8000 is already in use"

**Solution**: Use a different port with `php artisan serve --port=8001`

### Blade Template Errors

**Problem**: Template syntax errors or missing variables

**Solution**:
1. Check template syntax in views
2. Verify controller passes all required variables
3. Use `dd()` or `dump()` for debugging

---

## Next Steps

1. **Explore the Dashboard**: Review the system overview and available features
2. **Create a Test Project**: Set up a new project and practice the workflow
3. **Enter Sample Timesheets**: Get familiar with timesheet entry and approval
4. **Generate Reports**: Run reports to understand data flow and analysis capabilities
5. **Review Sample Data**: Examine the seeded data to understand system relationships
6. **Customize**: Adapt cost codes, employees, and settings to your needs
7. **Configure Authentication**: Add security measures for production use
8. **Set Up Backups**: Implement database backup procedures
9. **Train Users**: Provide team training on system usage
10. **Go Live**: Deploy to production environment with proper security measures

---

## Support & Resources

- **Laravel Documentation**: https://laravel.com/docs
- **MySQL Documentation**: https://dev.mysql.com/doc/
- **Tailwind CSS**: https://tailwindcss.com/docs
- **Alpine.js**: https://alpinejs.dev/

---

**Version**: 1.0
**Last Updated**: 2026-03-29
