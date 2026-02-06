# Event Manager Plugin - Quick Start Guide

## Installation

1. **Upload the Plugin**
   - Download all files in the `event-manager` folder
   - Upload to `/wp-content/plugins/event-manager/` on your WordPress installation
   - OR: Zip the `event-manager` folder and upload via WordPress admin (Plugins > Add New > Upload Plugin)

2. **Activate**
   - Go to Plugins in WordPress admin
   - Find "Event Manager" and click Activate

3. **Verify Installation**
   - You should see two new menu items: "Speakers" and "Venues"

## Quick Setup

### Step 1: Create Venues
1. Go to **Venues > Add New**
2. Add venue name, address, city, state, zip, country
3. Add capacity, phone, website
4. Set a featured image
5. Publish

### Step 2: Create Speakers
1. Go to **Speakers > Add New**
2. Add speaker name and biography
3. Set a featured image (speaker photo)
4. Publish

### Step 3: Create an Event
1. Go to **Pages > Add New**
2. Enter event title and description
3. In **Event Details** meta box:
   - Set start and end dates
   - Set registration deadline
   - Add registration URL
   - Select venue
   - Select speakers (check boxes)
   - Select organizers (check boxes)
   - Optionally select a parent event
4. In **Page Attributes** (right sidebar):
   - Select "Event Template" from Template dropdown
5. Publish

## File Structure

```
event-manager/
├── event-manager.php      # Main plugin file
├── templates/
│   └── event-template.php # Custom page template
├── assets/
│   └── css/
│       └── admin.css      # Admin styles
├── README.md              # Full documentation
└── DEVELOPER_GUIDE.md     # Developer documentation
```

## Features Overview

- ✅ JSON-based event data storage
- ✅ Custom post types: Speakers and Venues
- ✅ Event dates and registration deadline
- ✅ Registration URL with call-to-action button
- ✅ Venue integration with full address
- ✅ Multiple speakers per event
- ✅ Multiple organizers per event
- ✅ Hierarchical events (parent/child relationships)
- ✅ Responsive custom template
- ✅ REST API support for speakers and venues

## Troubleshooting

**Event Details box not showing?**
- Make sure you're editing a Page (not a Post)
- The meta box appears below the content editor

**Template not available?**
- Make sure the plugin is activated
- Check Page Attributes in the right sidebar

**Speakers/Venues not showing in event?**
- Create speakers/venues first before creating events
- Make sure they are published (not drafts)

## Next Steps

- Read the full **README.md** for detailed documentation
- Check **DEVELOPER_GUIDE.md** for custom development
- Customize the template by copying it to your theme

## Support

For issues or feature requests, modify the plugin files as needed or consult the developer documentation.
