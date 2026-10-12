**To:** support@paystack.com
**Subject:** Transfer Approval URL Not Being Called - Please Enable

---

Hi Paystack Support,

We have configured our Transfer Approval URL in the dashboard but it's not being called when we initiate transfers via API.

**Issue:**
- All transfers are being marked as BLOCKED in the dashboard
- Our approval endpoint at `https://kinvoice.ng/paystack/approve-transfer` is never called
- We have to manually approve each transfer in the dashboard


**Test Example:**
- Transfer Code: TRF_c9j5q48ptwnp28ea (₦500.00)
- Status: BLOCKED
- Our logs show no approval request received from Paystack

Could you please:
1. Enable the Transfer Control/Approval feature for our account
2. Verify our approval URL is properly configured in your system
3. Let us know if there are additional steps needed to activate this feature

We need Paystack to automatically call our approval endpoint when transfers are initiated so we can approve/decline programmatically instead of manually in the dashboard.

Thank you for your help.

Best regards,

Yomi
Khan Invoice
https://kinvoice.ng
