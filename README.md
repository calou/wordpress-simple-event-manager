# Event Manager WordPress Plugin

A comprehensive WordPress plugin for managing conferences, seminars, and other events with speakers, organizers, and hierarchical event relationships.

## Features

- **Custom Event Data**: Store event information as JSON metadata including:
  - Start date and time
  - End date and time
  - Registration deadline
  - Registration URL
  - Venue reference
  - Parent event relationships (events can belong to other events)

- **Speaker Management**: Custom post type for speakers with:
  - Name and biography
  - Featured images
  - Individual speaker pages
  - Link speakers to multiple events

- **Venue Management**: Custom post type for venues with:
  - Name and description
  - Full address details (street, city, state, zip, country)
  - Capacity information
  - Contact details (phone, website)
  - Featured images
  - Individual venue pages

- **Organizer Management**: 
  - Select WordPress users as event organizers
  - Display organizer contact information
  - Multiple organizers per event

- **Custom Page Template**: 
  - Professional event display template
  - Responsive design
  - Organized sections for all event information
  - Venue details with map-ready address
  - Registration call-to-action button
  - Related/sub-events display

- **Hierarchical Events**: 
  - Events can belong to parent events
  - Automatic display of sub-events
  - Perfect for conferences with multiple sessions

- **JSON Data Storage**:
  - All event data stored as a single JSON metadata field
  - Easy to query and manipulate
  - Efficient database usage

## Installation

1. Download the plugin files
2. Upload the entire `event-manager` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. You'll see two new menu items in your WordPress admin: "Speakers" and "Venues"

## Usage

### Creating Venues

1. Go to **Venues > Add New** in WordPress admin
2. Enter the venue name as the title
3. Add a description in the content editor
4. Set a featured image (venue photo)
5. Fill in the venue details:
   - Address
   - City, State/Province, ZIP/Postal Code
   - Country
   - Capacity
   - Phone number
   - Website URL
6. Publish the venue

### Creating Speakers

1. Go to **Speakers > Add New** in WordPress admin
2. Enter the speaker's name as the title
3. Add biography in the content editor
4. Set a featured image (speaker photo)
5. Publish the speaker

### Creating an Event Page

1. Go to **Pages > Add New**
2. Create your event page with title and content
3. Scroll down to the **Event Details** meta box
4. Fill in the event information:
   - Start Date
   - End Date
   - Registration Deadline
   - Registration URL (link to your registration form or ticketing page)
   - Venue (select from available venues)
   - Parent Event (optional)
   - Select Speakers (check all that apply)
   - Select Organizers (check all WordPress users who are organizing)

5. In the **Page Attributes** section on the right, select **Event Template** from the Template dropdown
6. Publish or update the page

### Viewing an Event

When you view a page with the Event Template applied, it will display:
- Event dates and registration deadline
- Registration call-to-action button (if registration URL is provided)
- Event description
- Venue information with full address, capacity, and contact details
- Speaker cards with photos and bios
- Organizer information with avatars
- Parent event information (if applicable)
- Related sub-events (if any)

### Creating Event Hierarchies

To create a main event with sub-events:

1. Create the main event page (e.g., "Annual Tech Conference 2024")
2. Create sub-event pages (e.g., "Morning Keynote", "Workshop Session")
3. In each sub-event, select the main event in the "Parent Event" dropdown
4. The main event page will automatically show all sub-events
5. Sub-event pages will show a link back to the parent event

## Template Customization

The plugin includes a built-in template at `templates/event-template.php`. If you want to customize it:

1. Copy `templates/event-template.php` to your theme directory
2. Modify the copied file as needed
3. Your theme's version will take precedence

## Helper Functions

The plugin provides helper functions you can use in your theme:

### `event_manager_get_event_data($post_id)`
Returns an array of event data for a specific post ID (from JSON metadata).

```php
$event_data = event_manager_get_event_data(123);
echo $event_data['start_date'];
echo $event_data['end_date'];
echo $event_data['registration_url'];
echo $event_data['venue_id'];
print_r($event_data['speaker_ids']);
print_r($event_data['organizer_ids']);
```

### `event_manager_get_venue_data($venue_id)`
Returns an array of venue data for a specific venue ID.

```php
$venue = event_manager_get_venue_data(456);
echo $venue['name'];
echo $venue['address'];
echo $venue['city'];
echo $venue['capacity'];
echo $venue['phone'];
echo $venue['website'];
```

### `event_manager_format_date($datetime_string, $format)`
Formats a datetime string in a readable format.

```php
$formatted = event_manager_format_date($event_data['start_date'], 'F j, Y g:i A');
// Output: January 15, 2024 9:00 AM
```

## Database Structure

The plugin stores event data as a single JSON post meta field:
- `_event_data` - JSON string containing all event information:
  ```json
  {
    "start_date": "2024-03-15T09:00",
    "end_date": "2024-03-15T17:00",
    "registration_deadline": "2024-03-10T23:59",
    "registration_url": "https://example.com/register",
    "venue_id": 123,
    "parent_event_id": 456,
    "speaker_ids": [789, 790],
    "organizer_ids": [1, 2, 3]
  }
  ```
- `_is_event_page` - Flag to quickly identify event pages

Venue data is stored as individual post meta fields:
- `_venue_address` - Street address
- `_venue_city` - City name
- `_venue_state` - State/Province
- `_venue_zip` - ZIP/Postal code
- `_venue_country` - Country name
- `_venue_capacity` - Maximum capacity
- `_venue_phone` - Contact phone
- `_venue_website` - Website URL

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher

## Support

For issues or questions, please contact the plugin developer.

## License

GPL v2 or later

## Changelog

### Version 1.0.0
- Initial release
- Speaker custom post type
- Venue custom post type with detailed location information
- Event meta boxes for pages
- JSON-based event data storage
- Registration URL support
- Custom event template with responsive design
- Hierarchical event relationships
- Speaker and organizer management
- Venue integration with full address and contact details
