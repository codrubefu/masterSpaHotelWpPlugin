# Hotel Rooms Import for WooCommerce

This plugin imports hotel rooms from your API endpoint and creates WooCommerce products with hotel-specific attributes.

## Features

- Import hotel rooms from paginated API endpoints
- Create WooCommerce products with room-specific metadata
- Automatic room type categorization
- Update existing products or create new ones
- Progress tracking and detailed logging
- Admin interface for easy management

## Usage

### Via Admin Interface

1. Go to **Tools → Import Hotel Rooms** in your WordPress admin
2. Enter your API URL (default: `http://localhost:8082/api/camerehotel`)
3. Choose your import options:
   - Update existing products
   - Create room type categories automatically
4. Click "Import Rooms" and watch the progress

### Programmatically

```php
// Basic import
$result = import_hotel_rooms('http://localhost:8082/api/camerehotel');

// With options
$result = import_hotel_rooms(
    'http://localhost:8082/api/camerehotel', 
    true,  // update existing
    true   // create categories
);

// Check results
if ($result) {
    echo "Imported: " . $result['imported'];
    echo "Updated: " . $result['updated'];
    echo "Errors: " . $result['errors'];
}
```

## API Data Structure

The API endpoint should return JSON data in this format:

```json
{
    "current_page": 1,
    "data": [
        {
            "idcamerehotel": 1,
            "nr": "100",
            "virtual": false,
            "tiplung": "Apartament",
            "idhotel": 1,
            "etajresel": 1,
            "adultMax": 2,
            "kidMax": 0,
            "babyBed": null,
            "bed": null
        }
    ],
    "last_page": 3,
    "per_page": 15,
    "total": 36
}
```

## Product Metadata

Each imported room creates a WooCommerce product with these custom fields:

- `_hotel_room_number` - Room number (e.g., "100")
- `_hotel_room_id` - Unique room ID from API
- `_hotel_room_type` - Room type description
- `_hotel_room_floor` - Floor number
- `_hotel_adults_max` - Maximum adults capacity
- `_hotel_kids_max` - Maximum children capacity
- `_hotel_baby_bed` - Baby bed availability
- `_hotel_bed_info` - Bed information
- `_hotel_virtual` - Virtual room flag
- `_hotel_id` - Hotel ID
- `_hotel_label_id` - Label ID

## Product Attributes

Visible product attributes include:
- Room Number
- Room Type
- Floor
- Maximum Adults
- Maximum Children

## Categories

Room types are automatically created as WooCommerce product categories (e.g., "Apartament", "Dubla matrimoniala", "Dubla twin").

## Stock Management

- Each room is set to manage stock with quantity 1
- Rooms are sold individually (can't buy multiple of the same room)
- Virtual rooms are marked appropriately in WooCommerce

## Error Handling

- API connection errors are logged
- Individual room import errors don't stop the process
- Detailed logging available in the admin interface
- Progress tracking with visual feedback

## Requirements

- WordPress with WooCommerce installed
- PHP 7.0 or higher
- Active internet connection for API access
- Appropriate WordPress permissions (manage_options capability)
