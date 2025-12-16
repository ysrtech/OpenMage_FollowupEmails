# Quick Start Guide - Email Autoresponders for Magento

## Overview
Powerful scheduled email notifications using **Magento's native email system**. No third-party accounts required!

## Features
✅ Event-based email automation  
✅ Abandoned cart recovery  
✅ Flexible scheduling (immediate or delayed)  
✅ Native Magento email templates  
✅ Product-specific triggers  
✅ Order lifecycle notifications  

## Quick Setup (5 Steps)

### 1. Install the Module
- Upload files to your Magento installation
- Clear Magento cache: `System > Cache Management > Flush Magento Cache`
- Database upgrade runs automatically
- Module version: **2.8.0.0**

### 2. Configure Settings
**Path:** `System > Configuration > Email Autoresponders`

- **Auto-subscribe after order:** Yes/No (optional - adds customers to newsletter)
- **Abandoned Cart Threshold:** Hours of inactivity before cart is considered abandoned (default: 1)

### 3. Create Email Templates
**Path:** `System > Transactional Emails`

Click **Add New Template** and customize:
- Select a base template or start from scratch
- Add your content and styling
- Use template variables (see below)
- Save template

### 4. Create Autoresponders
**Path:** `Email Autoresponders > Autoresponders`

Click **Add New Autoresponder** and configure:

| Field | Description | Example |
|-------|-------------|---------|
| **Event Trigger** | What triggers the email | "Order - New Order" |
| **Send Moment** | When to send | "When triggered" or "After 2 days" |
| **After Days/Hours** | Delay time | 2 days, 3 hours |
| **Email Template** | Which template to use | Your custom template |
| **Name** | Internal name | "Order Thank You - Day 2" |
| **Store View** | Which stores | All stores or specific |
| **Active From/To** | Optional date range | Dec 1 - Dec 31 |
| **Status** | Enable/disable | Active |

### 5. Test It!
- Trigger an event (place order, create shipment, etc.)
- Wait up to 5 minutes for cron to run
- Check your email inbox
- View log at: `Email Autoresponders > Autoresponders Log`

---

## Available Event Triggers

### 1. **Order - New Order**
Fires when a customer places an order
- **Best for:** Thank you emails, order confirmations, special offers
- **Variables:** `{{var order}}`, `{{var customer_name}}`

### 2. **Order - Order Status Changes**  
Fires when order status changes (pending → processing → complete)
- **Best for:** Status-specific notifications, completion follow-ups
- **Configure:** Select which status triggers the email
- **Variables:** `{{var order}}`, `{{var order.status}}`

### 3. **Order - Bought Specific Product**
Fires when a specific product is purchased
- **Best for:** Product instructions, warranty info, registration
- **Configure:** Enter Product ID
- **Variables:** `{{var order}}`, product data

### 4. **Abandoned Cart** ⭐
Fires when customer leaves items in cart without purchasing
- **Best for:** Cart recovery, special discounts
- **Configure:** Set threshold in configuration (hours of inactivity)
- **Variables:** `{{var quote}}`, `{{var recovery_url}}` (one-click restore)

### 5. **New Shipment**
Fires when order is shipped
- **Best for:** Shipping notifications, tracking info, delivery reminders
- **Variables:** `{{var shipment}}`, `{{var tracking_number}}`, `{{var tracking_title}}`

### 6. **New Invoice**  
Fires when invoice is created
- **Best for:** Payment confirmations, receipt follow-ups
- **Variables:** `{{var invoice}}`, `{{var order}}`

### 7. **New Creditmemo**
Fires when refund/credit memo is issued  
- **Best for:** Refund confirmations, apology emails
- **Variables:** `{{var creditmemo}}`, `{{var order}}`

---

## Template Variables

### Always Available:
```
{{var customer_name}}           - Customer's full name
{{var customer_email}}          - Customer's email address
```

### Order Events:
```
{{var order.increment_id}}      - Order number (#100012345)
{{var order.customer_firstname}}- First name
{{var order.customer_lastname}} - Last name
{{var order.grand_total}}       - Order total
{{var order.status}}            - Order status
{{var order.created_at}}        - Order date
```

### Abandoned Cart:
```
{{var quote.items_count}}       - Number of items in cart
{{var quote.grand_total}}       - Cart total amount
{{var cart_url}}                - Link to cart page
{{var checkout_url}}            - Link to checkout  
{{var recovery_url}}            - One-click recovery link ⭐
```

### Shipment:
```
{{var shipment.increment_id}}   - Shipment number
{{var tracking_number}}         - Tracking number
{{var tracking_title}}          - Carrier name
{{var shipment.created_at}}     - Ship date
```

### Invoice:
```
{{var invoice.increment_id}}    - Invoice number
{{var invoice.grand_total}}     - Invoice amount
{{var invoice.created_at}}      - Invoice date
```

### Credit Memo:
```
{{var creditmemo.increment_id}} - Credit memo number  
{{var creditmemo.grand_total}}  - Refund amount
{{var creditmemo.created_at}}   - Refund date
```

---

## Example Email Templates

### Example 1: Order Thank You (2 Days After Order)

**Autoresponder Settings:**
- Event: Order - New Order
- Send Moment: After
- After Days: 2
- After Hours: 0

**Email Subject:**
```
How was your experience with Order #{{var order.increment_id}}?
```

**Email Content:**
```html
<p>Hi {{var customer_name}},</p>

<p>Your order #{{var order.increment_id}} was delivered 2 days ago.</p>

<p>We'd love to hear your feedback! How was your experience?</p>

<p style="text-align: center;">
    <a href="{{store url='review'}}" style="background: #0066CC; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px;">
        Leave a Review
    </a>
</p>

<p>Thanks for choosing us!</p>
```

---

### Example 2: Abandoned Cart Recovery

**Autoresponder Settings:**
- Event: Abandoned Cart  
- Send Moment: When triggered (or After X hours for additional delay)

**Configuration:**
- Abandoned Cart Threshold: 1 hour

**Email Subject:**
```
You left {{var quote.items_count}} item(s) behind!
```

**Email Content:**
```html
<p>Hello {{var customer_name}},</p>

<p>We noticed you left <strong>{{var quote.items_count}} item(s)</strong> in your cart worth <strong>${{var quote.grand_total}}</strong>.</p>

<p>Good news - we've saved everything for you!</p>

<p style="text-align: center; margin: 30px 0;">
    <a href="{{var recovery_url}}" style="background: #FF6600; color: white; padding: 15px 40px; text-decoration: none; border-radius: 5px; font-size: 18px; font-weight: bold;">
        Complete My Purchase
    </a>
</p>

<p><small>This link will restore your cart and take you directly to checkout.</small></p>

<p>Questions? Reply to this email - we're here to help!</p>

<p>Best regards,<br>{{var store.name}}</p>
```

---

### Example 3: Shipment Notification

**Autoresponder Settings:**
- Event: New Shipment
- Send Moment: When triggered

**Email Subject:**
```
Your order #{{var order.increment_id}} has shipped! 📦
```

**Email Content:**
```html
<p>Great news, {{var customer_name}}!</p>

<p>Your order <strong>#{{var order.increment_id}}</strong> is on its way!</p>

{{if tracking_number}}
<div style="background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 5px;">
    <p><strong>Tracking Information:</strong></p>
    <p>Carrier: {{var tracking_title}}<br>
    Tracking Number: <strong>{{var tracking_number}}</strong></p>
</div>
{{/if}}

<p style="text-align: center;">
    <a href="{{var order.shipping_tracking_url}}" style="background: #28A745; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px;">
        Track Your Package
    </a>
</p>

<p>Expected delivery: 3-5 business days</p>
```

---

### Example 4: Product Review Request (7 Days After Shipment)

**Autoresponder Settings:**
- Event: New Shipment
- Send Moment: After  
- After Days: 7
- After Hours: 0

**Email Subject:**
```
Love your purchase? Share your thoughts! ⭐
```

**Email Content:**
```html
<p>Hi {{var customer_name}},</p>

<p>It's been a week since your order arrived. We hope you're enjoying your purchase!</p>

<p>Would you mind taking 2 minutes to share your experience? Your review helps other customers and helps us improve.</p>

<p style="text-align: center; margin: 30px 0;">
    <a href="{{store url='review/customer'}}" style="background: #FFC107; color: #333; padding: 15px 35px; text-decoration: none; border-radius: 5px; font-weight: bold;">
        Write a Review
    </a>
</p>

<p><strong>As a thank you, we'll send you a 10% discount code for your next order!</strong></p>

<p>Thank you for being an amazing customer!</p>
```

---

### Example 5: Order Status Change to Complete

**Autoresponder Settings:**
- Event: Order - Order Status Changes
- Order Status: complete
- Send Moment: When triggered

**Email Subject:**
```
Your order #{{var order.increment_id}} is complete!
```

**Email Content:**
```html
<p>Hi {{var customer_name}},</p>

<p>Wonderful news! Your order <strong>#{{var order.increment_id}}</strong> has been marked as complete.</p>

<p>We hope everything arrived as expected. If you have any questions or concerns, please don't hesitate to reach out.</p>

<p><strong>Need help?</strong></p>
<ul>
    <li>Returns: <a href="{{store url='returns'}}">Start a Return</a></li>
    <li>Support: <a href="mailto:support@yourstore.com">support@yourstore.com</a></li>
    <li>FAQ: <a href="{{store url='faq'}}">Visit our FAQ</a></li>
</ul>

<p>Thank you for your order!</p>
```

---

## Common Autoresponder Strategies

### 📧 **Welcome Series**
1. Order placed → Thank you (immediate)
2. Order shipped → Shipping notification (when triggered)
3. 7 days after ship → Review request

### 🛒 **Cart Recovery Sequence**
1. Cart abandoned → Recovery email #1 (1 hour)
2. Still not purchased → Recovery email #2 with discount (24 hours)
3. Last chance → Final reminder (48 hours)

### 🎁 **Product-Specific Follow-ups**
- High-value items → Extended warranty offer (3 days)
- Tech products → Setup guide (immediate)
- Consumables → Reorder reminder (30 days)

### 📦 **Post-Purchase Engagement**
1. Order complete → Thank you (immediate)
2. 2 days later → "How's it going?" email
3. 7 days later → Review request
4. 30 days later → Complementary product suggestions

---

## Technical Details

### Cron Jobs

Two cron jobs power the autoresponder system:

| Job | Schedule | Purpose |
|-----|----------|---------|
| **Send Autoresponders** | Every 5 minutes (`*/5 * * * *`) | Sends scheduled emails |
| **Check Abandoned Carts** | Every hour (`0 * * * *`) | Detects abandoned carts |

### Email Timing

- **"When triggered"**: Sent within 5 minutes of event
- **"After X days/hours"**: Sent within 5 minutes of scheduled time
- **Accuracy**: ±5 minutes (based on cron frequency)

### How It Works

1. **Event occurs** (order placed, cart abandoned, etc.)
2. **Event logged** in `fidelitas_autoresponders_events` table
3. **Send time calculated** (immediate or future)
4. **Cron checks** every 5 minutes for emails to send
5. **Email sent** via Magento's native email system
6. **Marked complete** with timestamp

---

## Administration

### View Scheduled Emails
**Path:** `Email Autoresponders > Autoresponders Log`

See all:
- Pending emails (waiting to be sent)
- Scheduled send times
- Sent emails with timestamps
- Customer details

### Manage Autoresponders  
**Path:** `Email Autoresponders > Autoresponders`

Grid shows:
- Autoresponder name
- Event trigger
- Email template used
- Total emails sent
- Active status
- Active date range

### Configuration
**Path:** `System > Configuration > Email Autoresponders`

Settings:
- Auto-subscribe customers to newsletter after order
- Abandoned cart threshold (hours)

---

## Troubleshooting

### Emails Not Sending

**Check these:**
1. ✅ Autoresponder status is "Active"
2. ✅ Email template is assigned
3. ✅ Active From/To dates don't block sending
4. ✅ Store view is correct
5. ✅ Magento cron is running
6. ✅ Check logs: `var/log/exception.log` and `var/log/fidelitas.log`

**Test Magento email:**
```
System > Configuration > Advanced > System > Mail Sending Settings
```

### Abandoned Cart Not Triggering

**Verify:**
1. ✅ Threshold configured in settings
2. ✅ Cart has customer email
3. ✅ Cart is still active (not converted to order)
4. ✅ Abandoned cart cron is running (hourly)
5. ✅ Cart age exceeds threshold

**Check cart:**
```sql
SELECT * FROM sales_flat_quote 
WHERE is_active = 1 
AND items_count > 0 
AND customer_email IS NOT NULL;
```

### Variables Not Working

**Common issues:**
- ✅ Use correct syntax: `{{var variable_name}}`
- ✅ Variable exists for that event type  
- ✅ No typos in variable names
- ✅ Test with simple variables first

### Cron Not Running

**Verify cron setup:**
```bash
# Check if cron is configured
crontab -l

# Should see something like:
*/5 * * * * php /path/to/magento/cron.php
```

**Or use:**
```
System > Configuration > Advanced > System > Cron
```

---

## Best Practices

### ✅ Do's

- **Test thoroughly** before activating
- **Use clear subject lines** that indicate purpose
- **Include unsubscribe option** in marketing emails
- **Personalize** with customer name and order details
- **Keep it short** and focused on one action
- **Mobile-friendly** design (responsive)
- **Add recovery links** for abandoned carts
- **Monitor performance** in Autoresponders Log

### ❌ Don'ts

- **Don't spam** - space out emails appropriately
- **Don't send too early** - give customers time
- **Don't forget to test** templates with real data
- **Don't use generic content** - personalize!
- **Don't ignore bounces** - monitor email deliverability
- **Don't forget date ranges** for seasonal campaigns

---

## FAQ

**Q: Do customers need to be subscribed to newsletters?**  
A: No! Autoresponders send to all customers who trigger events, regardless of subscription status.

**Q: Can I send to specific customer groups?**  
A: Use store views to target different stores, or create custom logic in email templates.

**Q: Do autoresponders work with API-created orders?**  
A: Yes! Works with admin, frontend, and API orders/shipments/etc.

**Q: Can I preview emails before sending?**  
A: Yes, use Magento's email template preview feature in `System > Transactional Emails`.

**Q: How do I stop an autoresponder temporarily?**  
A: Set Status to "Inactive" or use Active To date.

**Q: Can I use custom variables?**  
A: Yes! Access any property of order, quote, shipment, etc. objects.

**Q: What happens if template is deleted?**  
A: Autoresponder won't send. Check logs for errors.

**Q: Can I test without real orders?**  
A: Yes, create test orders or manually insert into `fidelitas_autoresponders_events` table.

---

## Support

### Logs
- **Exception log:** `var/log/exception.log`
- **System log:** `var/log/system.log`  
- **Fidelitas log:** `var/log/fidelitas.log`

### Database Tables
- **Autoresponders:** `fidelitas_autoresponders`
- **Scheduled emails:** `fidelitas_autoresponders_events`
- **Quotes (carts):** `sales_flat_quote`

### Useful SQL Queries

**View pending emails:**
```sql
SELECT * FROM fidelitas_autoresponders_events 
WHERE sent = 0 
ORDER BY send_at ASC;
```

**View sent emails:**
```sql
SELECT * FROM fidelitas_autoresponders_events 
WHERE sent = 1 
ORDER BY sent_at DESC 
LIMIT 20;
```

**View active autoresponders:**
```sql
SELECT * FROM fidelitas_autoresponders 
WHERE active = 1;
```

---

## Need Help?

If you encounter issues:
1. Check this guide first
2. Review logs
3. Test with simple setup
4. Verify cron is running
5. Check Magento email configuration

**Happy automating! 🚀**
```
Event: Order - New Order
Send Moment: When triggered
Send Method: Email
Template: Your custom order template
```

### Shipment Notification
```
Event: New Shipment
Send Moment: When triggered
Send Method: Email
Template: Shipment notification template
Variables available: {{var tracking_number}}, {{var tracking_title}}
```

### Follow-up After 3 Days
```
Event: Order - New Order
Send Moment: After 3 days
Send Method: Email
Template: Follow-up email template
```

### Order Status Change
```
Event: Order - Order Status Changes
New Status: Complete
Send Moment: When triggered
Send Method: Email
Template: Order complete template
```

## Email Template Variables

Use these in your email templates:

### Always Available
- `{{var customer_name}}` - Customer's full name
- `{{var customer_email}}` - Customer's email address

### Order Events (New Order, Status Change)
- `{{var order.increment_id}}` - Order number
- `{{var order.created_at}}` - Order date
- `{{var order.grand_total}}` - Order total
- Full order object available as `{{var order}}`

### Shipment Events
- `{{var shipment}}` - Shipment object
- `{{var tracking_number}}` - Tracking number
- `{{var tracking_title}}` - Carrier name
- `{{var order}}` - Related order

### Invoice Events
- `{{var invoice}}` - Invoice object
- `{{var order}}` - Related order

### Credit Memo Events
- `{{var creditmemo}}` - Credit memo object
- `{{var order}}` - Related order

## Example Email Template

**Subject:**
```
Order #{{var order.increment_id}} - Thank you for your order!
```

**Body:**
```html
<p>Dear {{var customer_name}},</p>

<p>Thank you for your order <strong>#{{var order.increment_id}}</strong>.</p>

<p>Order Total: {{var order.grand_total}}</p>

<p>We'll notify you when your order ships.</p>

<p>Best regards,<br>
Your Store Team</p>
```

## Troubleshooting

### ❌ Emails not sending?
1. ✅ Check autoresponder is **Active**
2. ✅ Verify **Email Template** is selected
3. ✅ Confirm cron is running: `*/5 * * * *`
4. ✅ Check logs: `var/log/exception.log`
5. ✅ Test Magento email: System > Configuration > Advanced > System > Mail Sending Settings

### ❌ Wrong template used?
- Verify correct template selected in autoresponder
- Clear cache after template changes
- Test with a new order/event

### ❌ Variables not showing?
- Check template syntax: `{{var variable_name}}`
- Ensure variable exists for that event type
- Test in System > Transactional Emails


---

**Version**: 2.8.0.0  
**Last Updated**: December 2025
