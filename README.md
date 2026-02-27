# Custom Page Reorder

A simple yet powerful WordPress plugin that allows you to reorder your pages with drag and drop functionality directly from the WordPress admin.

## Description

Custom Page Reorder provides an intuitive way to organize your WordPress pages. No more complicated page ordering systems—just drag and drop your pages into the desired order.

### Features

- **Drag & Drop Reordering** - Easily reorder pages by dragging them in the list
- **Dedicated Reorder Page** - Access a dedicated interface under Pages > Reorder Pages
- **Order Column** - See the order number directly in the pages list table
- **Multiple Page Statuses** - Reorder published, draft, pending, and private pages
- **Persistent Order** - Page order is saved permanently and survives updates

## Installation

1. Upload the `custom-page-reorder` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Pages > Reorder Pages** to arrange your pages
4. Alternatively, reorder directly from the Pages list table using the drag handle

## Frequently Asked Questions

### Does this plugin work with custom post types?

Currently, this plugin only supports WordPress pages. Support for custom post types may be added in future versions.

### Will my page order affect the front-end display?

The plugin sets the `menu_order` for each page, which is used by WordPress when pages are ordered by menu order. You can use this in your theme templates.

### Does it work with WordPress Multisite?

Yes, the plugin is compatible with WordPress Multisite installations.

## Changelog

### 1.0.0
- Initial release
- Drag and drop page reordering
- Dedicated reorder interface
- Order column in pages list

## License

GPL v2 or later
