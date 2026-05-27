# Loyalty Program Plugin

A comprehensive WordPress loyalty program plugin with integrations and reward management.

## Features

- **Points Management**: Award and track customer loyalty points
- **WooCommerce Integration**: Automatic points for orders, personal coupons, refund handling
- **Multi-Currency Support**: Automatic conversion to base currency for consistent point awards
- **SalesManago Integration**: Sync user data, consents, and track customer activities
- **Surveys & Quizzes**: Create interactive surveys and quizzes with point rewards
- **Rewards System**: Manage and distribute rewards to loyal customers
- **Wheel of Fortune**: Interactive prize wheel with configurable rewards
- **User Dashboard**: Track member activity and statistics
- **Auto-Enrollment**: Automatically add new users to the loyalty program
- **Multilingual Ready**: Full Polish translation included

## Installation

1. Upload the `loyalty-program` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin settings in the 'Loyalty Program' menu

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Configuration

### General Settings

Navigate to **Loyalty Program > Settings** to configure:

- **Enable/disable the loyalty program**: Master switch for all plugin functionality
- **Auto-enrollment on Registration**: Automatically add new users to the loyalty program when they create an account
- **Points per currency unit**: Set how many points customers earn per currency spent
- **Points for customer actions**: Configure points for:
  - Joining the loyalty program
  - Writing product reviews
  - Using coupon codes
  - Completing profile information
  - Adding birth date
  - Signing up for notifications
  - Return for More (repeat purchases)
  - Live expert sessions
  - Flash Hunter coupons
  - Attendance Master
  - Supplementation Discipline (3 purchases in 3 months)
- **WooCommerce Integration**: Personal coupon settings
- **User Account Fields**: Configure which additional fields display in WooCommerce "My Account"
- **Wheel of Fortune**: Configure prizes, probabilities, and spin intervals

### Integrations

Navigate to **Loyalty Program > Integrations** to set up:

- **SalesManago**: Marketing automation platform integration

  - **Enable/Disable**: Toggle integration on/off
  - **Credentials**: Enter Client ID, SHA, API Key, and Owner Email
  - **Test Connection**: Verify credentials before saving
  - **Verify Email**: Check if an email exists in your SalesManago account
  - **Features**:
    - Automatic user data synchronization (name, email, phone, birth date, address)
    - Consent management (SMS notifications, email newsletter)
    - User profile updates sync automatically
    - Check user consents via shortcode `[loyalty_check_consents]`
    - Detailed logging for all API operations

- **WooCommerce Multi Currency**: Multi-currency support for your store
  - **Status**: Automatic detection if plugin is installed and active
  - **Currency Display**: View all configured currencies with exchange rates
  - **Automatic Conversion**: Points are calculated based on base currency (PLN) value
    - Example: Order for 10 EUR with rate 0.2222222 = 45 PLN = 45 points
    - Ensures consistent point values regardless of customer's selected currency
  - **Supported Features**:
    - Multiple currencies with custom exchange rates
    - Automatic conversion on order completion
    - Detailed logging of currency conversions
    - Base currency detection (typically PLN)

### Users

Navigate to **Loyalty Program > Users** to manage users:

- **Search Users**: Find users by username or email address
  - Real-time search with debounce (searches as you type)
  - View user details: ID, username, email, first name, last name, registration date
  - Shows user avatar and display name
  - Maximum 50 results per search
- **Membership Management**:
  - View if user is enrolled in the loyalty program
  - See enrollment date for members
  - Enroll or remove users from the program
  - Automatic signup points awarded on enrollment
  - Color-coded status badges (Active/Not a member)
- **Points Management**:
  - View current points balance and total earned points
  - Complete points transaction history
  - Add or remove points manually with action description
  - Track all point changes with date, type, and reason
  - Points stored securely in user meta as array
- **Points Tracking**:
  - Current Points: Active balance available to use
  - Total Earned: Lifetime points earned
  - Total Spent: Lifetime points redeemed/used

### Rewards

Navigate to **Loyalty Program > Rewards** to configure rewards:

- **Editable Rewards Table**: Manage all rewards in one place
  - Drag & drop to reorder rewards
  - Add/delete reward rows dynamically
  - Enable/disable individual rewards with toggle
- **Reward Configuration**:
  - Reward Name: Custom name for the reward
  - Product: Select WooCommerce product to award
  - Points Required: Points needed to redeem
  - Price: Product price (default 0.01 PLN - 1 grosz)
  - Actions: Enable/disable toggle + delete button
- **Features**:
  - Live row numbering (auto-updates on reorder)
  - Form validation before saving
  - Disabled rewards shown in gray
  - WooCommerce integration for product selection
  - Saves all changes with single "Save Rewards" button

### Surveys & Quizzes

Navigate to **Loyalty Program > Surveys & Quizzes** to create interactive content:

- **Survey vs Quiz**:
  - **Survey**: Collect user opinions and feedback (no right/wrong answers)
  - **Quiz**: Test knowledge with correct answers and scoring
- **Question Types**:
  - Text input (short and long)
  - Multiple choice (radio buttons)
  - Checkboxes (multiple selections)
  - Star rating (1-5 stars)
  - Number input
- **Configuration**:
  - Completion points (awarded for finishing)
  - Quiz points (bonus for correct answers)
  - Time limits (optional)
  - Thank you message
- **Management**:
  - Drag & drop to reorder questions
  - Enable/disable surveys
  - Copy shortcode with one click
  - View detailed results with scores
  - Export results to CSV
- **Shortcode**: `[loyalty_survey id="survey_xxx"]` - embeds survey on any page
- **Results Dashboard**: View completion rates, scores, and individual responses

### Live with Expert

Navigate to **Loyalty Program > Live with Expert** to award points for live sessions:

- **CSV Import**: Upload a CSV file with user email addresses
- **Session Title**: Custom title for each live session (appears in points history)
- **Automatic Processing**:
  - Validates email addresses
  - Finds matching WordPress users
  - Checks loyalty program enrollment
  - Awards configured points (default: 30 points)
- **Detailed Report**: Shows success/failed counts with error details
- **Full Logging**: All operations logged in debug log
- **CSV Format**: Simple one-column format (email addresses)
- **Sample File**: `sample-live-participants.csv` included in plugin directory

### Shortcodes

Navigate to **Loyalty Program > Shortcodes** for available shortcodes:

#### User Information

- **[loyalty_current_points]**: Display current points balance (members only)
- **[loyalty_total_points]**: Display total lifetime points earned (members only)
- **[loyalty_membership_status]**: Show membership status with join button for non-members

#### Points & History

- **[loyalty_points_history limit="10"]**: Display points transaction history with optional limit
- **[loyalty_join_program]**: Button to join the loyalty program

#### Coupons & Rewards

- **[loyalty_user_coupon]**: Display personal discount coupon with copy button (members only)
- **[loyalty_my_rewards]**: Show available rewards catalog with redemption options
- **[loyalty_redeemed_rewards]**: Display rewards already redeemed by user

#### Interactive Features

- **[loyalty_wheel_of_fortune]**: Spin the prize wheel to win points (configurable prizes)
- **[loyalty_survey id="survey_xxx"]**: Embed a specific survey or quiz

#### Profile Management

- **[loyalty_birth_date]**: Form to add/update birth date (earns points on first completion)
- **[loyalty_consents]**: Manage SMS and newsletter consent preferences

#### SalesManago Integration

- **[loyalty_check_consents]**: Check user consent status in SalesManago (members only)

**Note**: Most shortcodes require users to be logged in and enrolled in the loyalty program. Appropriate messages are displayed for guests and non-members.

### Wheel of Fortune

Configure the wheel of fortune in **Settings > Wheel of Fortune Configuration**:

- **Days Between Spins**: How many days users must wait between spins (default: 7 days)
- **Wheel Prizes**: Add, edit, reorder prizes with points and probabilities
- Each prize can be enabled/disabled individually
- Probability-based prize selection (or evenly distributed if not set)
- Beautiful animated canvas wheel with smooth rotation
- Auto-records prize wins in user's points history
- Admin can reset wheel for any user in Users panel

### Developer Panel

Navigate to **Loyalty Program > Developer Panel** for development tools:

- **Debug Settings**: Enable custom debug logging (max 5 MB)
  - Log file location: `loyalty-program/logs/debug.log`
  - View, download, and clear debug logs
  - Real-time log viewer with last 100 entries
  - Protected by .htaccess (deny from all)
- **Asset Versioning**: Control CSS/JS file versioning
  - Custom version number for cache control
  - Random version generation for development (cache busting)
- **Custom Integrations**: Code examples for developers
  - **Review Points Integration**: Award points for custom review systems
  - **Check User Membership**: Code snippets to verify if user is a loyalty member
  - Example usage with `Loyalty_Program_Points::is_member($user_id)`
  - Access to points retrieval functions
- **System Information**: View WordPress, PHP, and MySQL versions

## Development

The plugin is built with an object-oriented architecture using WordPress best practices:

### Core Classes

- **Main Class**: `Loyalty_Program` - Core plugin functionality and initialization
- **Admin Class**: `Loyalty_Program_Admin` - Admin area management and asset loading
- **Menu Class**: `Loyalty_Program_Admin_Menu` - Menu registration and page rendering
- **AJAX Class**: `Loyalty_Program_Ajax` - AJAX request handlers for all operations
- **Points Class**: `Loyalty_Program_Points` - Points management, tracking, and membership
- **WooCommerce Class**: `Loyalty_Program_WooCommerce` - WooCommerce integration and hooks
- **Shortcodes Class**: `Loyalty_Program_Shortcodes` - Frontend shortcodes rendering
- **Logger Class**: `Loyalty_Program_Logger` - Custom debug logging system
- **Install Class**: `Loyalty_Program_Install` - Installation and database setup

### Integration Classes

- **Integrations Class**: `Loyalty_Program_Integrations` - Third-party integration management
- **SalesManago Class**: `Loyalty_Program_SalesManago` - SalesManago API wrapper with:
  - Contact upsert (create/update)
  - Contact retrieval
  - Consent management
  - User data synchronization
  - Full SHA signature generation

### Table Classes

- **Users Table**: `Loyalty_Program_Users_Table` - Extends WP_List_Table for user management
- **Survey Results Table**: `Loyalty_Program_Survey_Results_Table` - Survey completion tracking

### API Integration

All classes follow WordPress coding standards and support:

- Translation-ready strings
- Nonce verification for security
- Capability checks for permissions
- Extensive logging for debugging
- Error handling and validation

## WooCommerce Integration Features

When WooCommerce is active, the plugin automatically:

### Automatic Points for Orders

- Awards points when order status changes to "Completed"
- Points calculated based on order total × points_per_currency setting
- Amounts are rounded up (e.g., 100.99 PLN × 1 pt/PLN = 101 points)
- Adds order note documenting points awarded
- Prevents duplicate point awards

### Refund Handling

- Automatically removes points when order is refunded or cancelled
- Only removes points that were previously awarded
- Adds order note documenting points removal
- Prevents negative point balances

### Personal Coupon Generation

- Unique coupon code generated automatically when user joins loyalty program
- Format: LOYALTY-XXXXXXXX (8 random characters)
- Configurable discount value in Settings (default: 10 PLN)
- Coupon type: Fixed cart discount
- Unlimited uses, once per user per order
- No expiration date

### Coupon Usage Tracking

- Awards points to coupon owner when their personal coupon is used by anyone
- Configurable points amount (default: 10 points from points_coupon_use)
- Tracks coupon owner and order in points history
- Adds order note showing points awarded to coupon owner
- Full audit trail in debug logs

### Return for More Bonus

Encourages repeat purchases by rewarding customers who buy the same product again within 30 days:

- **Automatic Detection**: Tracks all product purchases with timestamps
- **30-Day Window**: Awards bonus points when customer repurchases the same product within 30 days
- **Configurable Points**: Set bonus points in Settings > Points for Customer Actions > Return for More (default: 50 points)
- **Smart Tracking**:
  - Stores purchase history in user meta (`loyalty_program_purchase_history`)
  - Checks every completed order for repeat purchases
  - Updates history with each new purchase
- **Detailed Logging**:
  - Records which product was repurchased
  - Shows how many days since last purchase
  - Adds order note with bonus details (e.g., "Return for More bonus: 50 points for 1 repeat purchase")
- **Multiple Products**: Awards bonus for each repeat product in the order
- **Points History**: Each bonus is recorded as "Return for More: {Product Name} (purchased again after X days)"
- **Disable Feature**: Set points to 0 to disable this feature

### Advanced Loyalty Features

#### Flash Hunter Coupons

Special time-limited coupons with bonus points:

- **Admin Coupon Fields**: Add Flash Hunter metadata to any WooCommerce coupon
- **Valid From/To**: Set exact start and end dates/times
- **Bonus Points**: Award extra points when Flash Hunter coupon is used
- **Order Tracking**: Logs Flash Hunter usage in order notes
- **Configurable**: Set points amount in Settings (default: 50 points)

#### Attendance Master

Rewards consistent participation in live sessions:

- **Automatic Tracking**: Monitors user attendance at live expert sessions
- **Milestone Rewards**: Awards bonus points for attending multiple sessions
- **CSV Integration**: Import participant lists from live sessions
- **Configurable Points**: Set reward amount in Settings (default: 50 points)

#### Supplementation Discipline

Encourages regular supplement purchases:

- **3-in-3 Challenge**: Buy the same product 3 times within 3 months
- **Automatic Detection**: Tracks product purchase patterns
- **Milestone Bonus**: Awards significant points upon completion
- **Progress Tracking**: Shows users their progress toward the goal
- **Counter Reset**: Automatically resets if not completed within 3 months
- **Configurable Points**: Set reward amount in Settings (default: 50 points)
- **Frontend Display**: Shows progress in product pages and rewards catalog

### File Structure

```
loyalty-program/
├── loyalty-program.php          # Main plugin file
├── includes/
│   ├── class-loyalty-program-install.php
│   ├── class-loyalty-program-points.php
│   ├── class-loyalty-program-woocommerce.php
│   ├── class-loyalty-program-shortcodes.php
│   ├── class-loyalty-program-logger.php
│   ├── admin/
│   │   ├── class-loyalty-program-admin.php
│   │   ├── class-loyalty-program-admin-menu.php
│   │   ├── class-loyalty-program-ajax.php
│   │   ├── class-loyalty-program-integrations.php
│   │   ├── class-loyalty-program-users-table.php
│   │   ├── class-loyalty-program-survey-results-table.php
│   │   └── views/
│   │       ├── dashboard.php
│   │       ├── integrations.php
│   │       ├── users.php
│   │       ├── rewards.php
│   │       ├── surveys.php
│   │       ├── survey-results.php
│   │       ├── shortcodes.php
│   │       ├── shortcodes-new.php
│   │       ├── live-expert.php
│   │       ├── settings.php
│   │       └── developer-panel.php
│   └── integrations/
│       └── class-loyalty-program-salesmanago.php
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── frontend.css
│   └── js/
│       ├── admin.js
│       └── frontend.js
├── logs/
│   ├── debug.log              # Debug log file (max 5 MB)
│   └── .htaccess              # Access protection
├── languages/
│   ├── loyalty-program.pot    # Translation template
│   ├── loyalty-program-pl_PL.po  # Polish translation (source)
│   └── loyalty-program-pl_PL.mo  # Polish translation (compiled)
├── sample-live-participants.csv # Sample CSV for Live with Expert
└── README.md
```

## Translation

The plugin is fully translated to Polish and ready for additional translations.

### Available Languages

- **English** (default)
- **Polish** (pl_PL) - Complete translation included

### Adding New Translations

To translate to your language:

1. Use `/languages/loyalty-program.pot` as a template
2. Create a `.po` file for your language (e.g., `loyalty-program-es_ES.po`)
3. Translate all strings
4. Compile to `.mo` file using msgfmt or a tool like Poedit
5. Place both `.po` and `.mo` files in `/languages` directory

All strings are wrapped in translation functions with the `loyalty-program` text domain.

## Support

For support, feature requests, or bug reports, please contact the plugin developer.

## License

GPL v2 or later

## Changelog

### 2.0.0

- **Major Update**: Complete redesign and feature expansion
- Added SalesManago integration (replaced Webankieta)
- Added WooCommerce Multi Currency support with automatic conversion
- Added Surveys & Quizzes system with multiple question types
- Added auto-enrollment option for new users
- Added user membership checking in all relevant shortcodes
- Enhanced user details modal with comprehensive information
- Improved CSV export functionality
- Added Polish translation (pl_PL)
- Added developer documentation for membership checking
- Enhanced points management with detailed logging
- Enhanced currency conversion: Points awarded based on base currency value
- Fixed multiple bugs related to point calculations and reward redemption
- Improved security with better nonce verification
- Added shortcode documentation in admin panel

### 1.0.0

- Initial release
- Basic loyalty program functionality
- Admin dashboard and settings
- WooCommerce integration
- Points management system
