# Translation Analysis Report

## Overview
This report analyzes all controllers and services for translation compliance according to `backend-translation-patterns.md`.

## Summary
- **Total Controllers Analyzed**: 24
- **Controllers with HasTranslations**: 8
- **Controllers Missing HasTranslations**: 1 (FinancialReportController)
- **Services with Translation Issues**: 1 (MareaService)
- **Total Issues Found**: 45+ hardcoded messages

## Issues Found

### 1. FinancialReportController ❌
**Status**: Missing HasTranslations trait, all messages hardcoded

**Issues**:
- Line 26: `abort(403, 'You do not have access to this vessel.');`
- Line 33: `abort(403, 'You do not have permission to view financial reports.');`
- Line 98: `abort(403, 'You do not have access to this vessel.');`
- Line 105: `abort(403, 'You do not have permission to view financial reports.');`
- Line 110: `abort(404, 'Invalid month.');`
- Line 114: `abort(404, 'Invalid year.');`
- Line 291: `abort(403, 'You do not have access to this vessel.');`
- Line 298: `abort(403, 'You do not have permission to view financial reports.');`
- Line 303: `abort(404, 'Invalid month.');`
- Line 307: `abort(404, 'Invalid year.');`
- Line 313: `abort(404, 'Vessel not found.');`

**Fix Required**: Add HasTranslations trait and translate all messages

---

### 2. MaintenanceController ⚠️
**Status**: Has HasTranslations, but some abort() calls are not translated

**Issues**:
- Line 148: `abort(403, 'You do not have access to this vessel.');`
- Line 154: `abort(403, 'You do not have permission to create maintenances.');`
- Line 190: `abort(404, 'Vessel not found.');`
- Line 196: `abort(403, 'You do not have access to this vessel.');`
- Line 202: `abort(403, 'You do not have permission to create maintenances.');`
- Line 334: `abort(404, 'Vessel not found.');`
- Line 345: `abort(404, 'Maintenance not found.');`
- Line 362: `abort(403, 'You do not have access to this vessel.');`
- Line 368: `abort(403, 'You do not have permission to view maintenances.');`
- Line 542: `abort(403, 'You do not have permission to view maintenances.');`
- Line 588: `abort(404, 'Vessel not found.');`
- Line 599: `abort(404, 'Maintenance not found.');`
- Line 612: `abort(403, 'You do not have access to this vessel.');`
- Line 618: `abort(403, 'You do not have permission to delete maintenances.');`
- Line 688: `abort(404, 'Vessel not found.');`
- Line 699: `abort(404, 'Maintenance not found.');`
- Line 716: `abort(403, 'You do not have permission to edit maintenances.');`
- Line 721: `abort(403, 'Cannot update a closed or cancelled maintenance.');`
- Line 798: `abort(404, 'Vessel not found.');`
- Line 809: `abort(404, 'Maintenance not found.');`
- Line 826: `abort(403, 'You do not have permission to edit maintenances.');`
- Line 901: `abort(404, 'Vessel not found.');`
- Line 912: `abort(404, 'Maintenance not found.');`
- Line 925: `abort(403, 'You do not have access to this vessel.');`
- Line 931: `abort(403, 'You do not have permission to edit maintenances.');`
- Line 936: `abort(403, 'Cannot remove transactions from a closed or cancelled maintenance.');`
- Line 947: `abort(404, 'Transaction not found.');`

**Fix Required**: Translate all abort() messages using transFrom()

---

### 3. MareaController ⚠️
**Status**: Has HasTranslations, but some abort() calls are not translated

**Issues**:
- Line 41: `abort(403, 'You do not have permission to view mareas.');`
- Line 162: `abort(403, 'You do not have access to this vessel.');`
- Line 168: `abort(403, 'You do not have permission to create mareas.');`
- Line 207: `abort(404, 'Vessel not found.');`
- Line 213: `abort(403, 'You do not have access to this vessel.');`
- Line 219: `abort(403, 'You do not have permission to create mareas.');`
- Line 302: `abort(403, 'You do not have access to this vessel.');`
- Line 308: `abort(403, 'You do not have permission to view mareas.');`
- Line 675: `abort(403, 'You do not have access to this vessel.');`
- Line 681: `abort(403, 'You do not have permission to edit mareas.');`
- Line 686: `abort(403, 'Cannot edit a closed or cancelled marea.');`
- Line 757: `abort(403, 'You do not have permission to delete mareas.');`
- Line 840: `abort(403, 'You do not have permission to manage marea status.');`
- Line 924: `abort(403, 'You do not have permission to manage marea status.');`
- Line 1008: `abort(403, 'You do not have permission to manage marea status.');`
- Line 1061: `abort(403, 'You do not have permission to manage marea status.');`
- Line 1115: `abort(403, 'You do not have permission to edit mareas.');`
- Line 1120: `abort(403, 'Cannot add transactions to a closed or cancelled marea.');`
- Line 1195: `abort(403, 'You do not have permission to edit mareas.');`
- Line 1200: `abort(403, 'Cannot remove transactions from a closed or cancelled marea.');`
- Line 1211: `abort(404, 'Movimentation not found.');`
- Line 1263: `abort(403, 'You do not have permission to edit mareas.');`
- Line 1268: `abort(403, 'Cannot add crew to a closed or cancelled marea.');`
- Line 1297: `abort(403, 'User does not belong to this vessel.');`
- Line 1358: `abort(403, 'You do not have permission to edit mareas.');`
- Line 1363: `abort(403, 'Cannot remove crew from a closed or cancelled marea.');`
- Line 1374: `abort(404, 'Crew member not found.');`
- Line 1425: `abort(403, 'You do not have permission to edit mareas.');`
- Line 1430: `abort(403, 'Can only add quantity returns to returned mareas. Closed mareas cannot be modified.');`
- Line 1508: `abort(404, 'Quantity return not found.');`
- Line 1557: `abort(403, 'You do not have access to this vessel.');`
- Line 1630: `abort(403, 'You do not have access to this vessel.');`
- Line 1699: `abort(403, 'You do not have access to this vessel.');`
- Line 1705: `abort(403, 'You do not have permission to edit mareas.');`
- Line 1834: `abort(403, 'You do not have access to this vessel.');`
- Line 1840: `abort(403, 'You do not have permission to edit mareas.');`
- Line 1845: `abort(403, 'Cannot edit a closed or cancelled marea.');`
- Line 1924: `abort(403, 'You do not have access to this vessel.');`
- Line 1930: `abort(403, 'You do not have permission to edit mareas.');`
- Line 1935: `abort(403, 'Cannot add salary payments to a closed or cancelled marea.');`
- Line 2048: `abort(403, 'You do not have access to this vessel.');`
- Line 2120: `abort(403, 'You do not have access to this vessel.');`
- Line 2127: `abort(403, 'You do not have permission to view mareas.');`

**Fix Required**: Translate all abort() messages using transFrom()

---

### 4. MovimentationController ⚠️
**Status**: Has HasTranslations, but some abort() calls are not translated

**Issues**:
- Line 333: `abort(404, 'Vessel not found.');`
- Line 438: `abort(404, 'Transaction not found.');`
- Line 499: `abort(404, 'Transaction not found.');`
- Line 553: `abort(404, 'Transaction not found.');`
- Line 682: `abort(404, 'Vessel not found.');`
- Line 693: `abort(404, 'Transaction not found.');`
- Line 904: `abort(404, 'Transaction not found.');`
- Line 1002: `abort(404, 'Transaction not found.');`
- Line 1015: `abort(404, 'File not found.');`
- Line 1367: `abort(404, 'Vessel not found.');`

**Fix Required**: Translate all abort() messages using transFrom()

---

### 5. CrewMemberController ⚠️
**Status**: Has HasTranslations, but some abort() calls are not translated

**Issues**:
- Line 609: `abort(403, 'Unauthorized access to crew member.');`
- Line 910: `abort(403, 'Unauthorized access to crew member. User does not have access to this vessel.');`
- Line 1154: `abort(403, 'This crew member does not belong to this vessel.');`
- Line 1219: `abort(403, 'This crew member does not belong to this vessel.');`

**Fix Required**: Translate all abort() messages using transFrom()

---

### 6. VesselController ⚠️
**Status**: Has HasTranslations, but some abort() calls are not translated

**Issues**:
- Line 87: `abort(403, 'You do not have permission to create vessels.');`
- Line 117: `abort(403, 'You do not have permission to create vessels. You must have tenant role (paid_system).');`
- Line 122: `abort(403, 'You must have tenant role (paid_system) to create vessels.');`
- Line 166: `abort(403, 'You can only view the current vessel.');`

**Fix Required**: Translate all abort() messages using transFrom()

---

### 7. SupplierController ⚠️
**Status**: Has HasTranslations, but some abort() calls are not translated

**Issues**:
- Line 78: `abort(403, 'You do not have permission to view suppliers.');`
- Line 135: `abort(403, 'You do not have permission to view suppliers.');`
- Line 154: `abort(403, 'You do not have permission to view suppliers.');`
- Line 271: `abort(403, 'You do not have permission to search suppliers.');`

**Fix Required**: Translate all abort() messages using transFrom()

---

### 8. RecycleBinController ⚠️
**Status**: Has HasTranslations, but some abort() calls are not translated

**Issues**:
- Line 38: `abort(403, 'You do not have permission to view the recycle bin.');`
- Line 232: `abort(403, 'You do not have access to this vessel.');`
- Line 239: `abort(403, 'You do not have permission to restore items.');`
- Line 312: `abort(403, 'You do not have access to this vessel.');`
- Line 319: `abort(403, 'You do not have permission to permanently delete items.');`
- Line 396: `abort(403, 'You do not have access to this vessel.');`
- Line 403: `abort(403, 'You do not have permission to empty the recycle bin.');`

**Fix Required**: Translate all abort() messages using transFrom()

---

### 9. MareaService ❌
**Status**: Missing HasTranslations trait, uses __() helper instead of transFrom()

**Issues**:
- Line 130: Uses `__('notifications.This marea number is already in use (even if deleted). Suggested number: :number', ...)`
- Line 136: Uses `__('notifications.This marea number is already in use. Please use a different number.')`
- Line 166: Uses `__('notifications.A soft-deleted marea with this number exists. Suggested number: :number', ...)`
- Line 199: Uses `__('notifications.This marea number is already in use. Please use the auto-generated number: :number', ...)`

**Fix Required**: 
- Add HasTranslations trait (if possible) OR
- Use proper translation pattern with user context
- Note: Services typically don't have user context, so __() might be acceptable, but should follow pattern

---

## Missing Translations in Language Files

The following messages need to be added to `lang/en/notifications.php`:

1. `'You do not have permission to create vessels. You must have tenant role (paid_system).'`
2. `'You must have tenant role (paid_system) to create vessels.'`
3. `'You can only view the current vessel.'`
4. `'You do not have permission to view suppliers.'`
5. `'You do not have permission to search suppliers.'`
6. `'You do not have permission to view the recycle bin.'`
7. `'You do not have permission to restore items.'`
8. `'You do not have permission to permanently delete items.'`
9. `'You do not have permission to empty the recycle bin.'`
10. `'Unauthorized access to crew member.'`
11. `'Unauthorized access to crew member. User does not have access to this vessel.'`
12. `'This crew member does not belong to this vessel.'`
13. `'Cannot add transactions to a closed or cancelled marea.'`
14. `'Cannot remove transactions from a closed or cancelled marea.'`
15. `'Cannot add crew to a closed or cancelled marea.'`
16. `'Cannot remove crew from a closed or cancelled marea.'`
17. `'Can only add quantity returns to returned mareas. Closed mareas cannot be modified.'`
18. `'Cannot add salary payments to a closed or cancelled marea.'`
19. `'Cannot update a closed or cancelled maintenance.'`
20. `'Cannot remove transactions from a closed or cancelled maintenance.'`
21. `'Invalid transaction ID.'`
22. `'Invalid user ID.'`
23. `'Invalid crew member ID.'`
24. `'Transaction not found.'`
25. `'Maintenance not found.'`
26. `'Movimentation not found.'`
27. `'Quantity return not found.'`
28. `'Vessel ':name' has been deleted successfully. All user access has been removed.'`

---

## Recommendations

1. **Add HasTranslations trait** to FinancialReportController
2. **Translate all abort() messages** in all controllers using `$this->transFrom('notifications', '...')`
3. **Add missing translations** to all language files (en, pt, es, fr)
4. **Review MareaService** - Consider if services should have user context for translations
5. **Create helper method** for common abort messages to reduce duplication

---

## Priority

1. **High Priority**: FinancialReportController (completely missing translations)
2. **High Priority**: Add missing translations to language files
3. **Medium Priority**: Fix all abort() messages in controllers
4. **Low Priority**: Review service translation patterns

