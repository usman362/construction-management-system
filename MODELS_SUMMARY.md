# Construction Management System - Eloquent Models Summary

All 31 Eloquent models have been successfully created in `/app/Models/`.

## Models Created

1. **Craft** - Trades/skills with hourly rates and multipliers
2. **Employee** - Employee data with soft deletes, includes craft relationship and full_name accessor
3. **Client** - Client/customer information
4. **Project** - Main project model with soft deletes, full financial tracking, profit accessors
5. **ProjectPhase** - Project phases for breaking down work
6. **CostCode** - Hierarchical cost code structure (parent/child relationships)
7. **Shift** - Work shift definitions with time and multipliers
8. **Crew** - Work crews assigned to projects with foreman
9. **CrewMember** - Crew membership tracking with assignment/removal dates
10. **BudgetLine** - Budget line items per cost code per project with current_amount accessor
11. **Vendor** - Vendor management with type classification and active/subcontractor/supplier scopes
12. **Commitment** - Purchase commitments/POs to vendors
13. **Invoice** - Vendor invoices linked to commitments
14. **ChangeOrder** - Change orders with items and labor details
15. **ChangeOrderItem** - Line items on change orders
16. **ChangeOrderLabor** - Labor estimates for change orders
17. **Timesheet** - Employee timesheets with cost allocations
18. **TimesheetCostAllocation** - Cost code allocation per timesheet
19. **CostEntry** - Generic cost entry tracking
20. **Equipment** - Equipment inventory with rental rates
21. **EquipmentAssignment** - Equipment assignments to projects
22. **Material** - Material inventory
23. **MaterialUsage** - Material usage tracking on projects
24. **Estimate** - Project estimates with line items
25. **EstimateLine** - Estimate line items
26. **ManhourBudget** - Manhour budgeting by cost code
27. **PayrollPeriod** - Payroll period definition
28. **PayrollEntry** - Individual payroll entries
29. **BillingInvoice** - Customer billing invoices with line item breakdown
30. **PerDiemRate** - Per diem rates per project
31. **DailyLog** - Daily project logs with weather and notes

## Key Features Implemented

### Casts
All models include proper decimal, date, datetime, and boolean casts for type safety.

### Fillable Arrays
All models include complete fillable arrays for mass assignment protection.

### Relationships
- BelongsTo relationships for all foreign keys
- HasMany relationships for all collections
- BelongsToMany for Crew-Employee join through crew_members
- Self-referencing for CostCode parent/children hierarchy

### Scopes
- `active` scope on Craft, Employee, CostCode, Vendor
- `topLevel` scope on CostCode (filters parent_id = null)
- Type-specific scopes on Vendor (subcontractors, suppliers)

### Accessors
- `full_name` on Employee (concatenates first_name + last_name)
- `profit` on Project (estimate - current_budget)
- `profit_percentage` on Project (profit/estimate * 100)
- `current_amount` on BudgetLine (revised_amount or budget_amount)

### Special Features
- SoftDeletes on Employee and Project
- Explicit table names where needed (crew_members, project_phases, cost_codes, etc.)
- User model relationships for created_by and approved_by fields
- Complete financial tracking with all decimal:2 casts
