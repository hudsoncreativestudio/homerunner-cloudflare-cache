# Homerunner Cloudflare Cache

A WordPress plugin that automatically clears the Cloudflare cache whenever a page, post, or custom post type is updated. This plugin helps ensure that visitors see the latest version of your website without manually purging the cache on Cloudflare.

## Features

- Automatically clears Cloudflare cache when pages, posts, or custom post types are updated.
- Allows you to store Cloudflare API credentials securely within WordPress settings.

## Installation

1. **Upload the Plugin Files**

   - Upload the plugin files to the `/wp-content/plugins/homerunner-cloudflare-cache` directory, or install the plugin directly through the WordPress plugin screen.

2. **Activate the Plugin**

   - Activate the plugin through the **Plugins** screen in WordPress.

3. **Configure Settings**
   - Navigate to **Settings > Homerunner Cloudflare Cache** to configure the plugin.
   - Enter your **Cloudflare API Token** and **Zone ID**.

## How to Generate the Cloudflare API Token

To clear the cache using this plugin, you need to create an API Token in Cloudflare with the correct permissions.

1. **Log in to Cloudflare**

   - Go to the [Cloudflare Dashboard](https://dash.cloudflare.com/) and log in to your account.

2. **Navigate to API Tokens**

   - Click on your profile icon in the top right corner.
   - Select **My Profile**.
   - On the left-hand menu, click on **API Tokens**.

3. **Create a Custom Token**

   - Click **Create Token**.
   - Choose **Create Custom Token**.
   - **Permissions**: Set **Zone** → **Cache Purge** → **Edit**.
   - **Zone Resources**: Select **Include** and either **All Zones** or a specific zone.

4. **Generate Token**

   - Name the token, e.g., **WordPress Cache Clear Token**.
   - Click **Continue to Summary** and then **Create Token**.
   - **Copy** the generated token immediately, as it will not be shown again.

5. **Enter the Token in Plugin Settings**
   - Go back to WordPress, navigate to **Settings > Homerunner Cloudflare Cache**, and paste the API Token.

## How to Find Your Zone ID

1. **Log in to Cloudflare**

   - Go to the [Cloudflare Dashboard](https://dash.cloudflare.com/) and log in to your account.

2. **Select Your Website**

   - Select the website you want to manage.

3. **Locate Zone ID**

   - On the overview page, you will see the **Zone ID** in the right-hand column under **API**.

4. **Enter the Zone ID in Plugin Settings**
   - Go to **Settings > Homerunner Cloudflare Cache** in WordPress, and enter the **Zone ID**.

## Usage

Once the plugin is set up with the API Token and Zone ID, it will automatically clear the Cloudflare cache whenever:

- A **page** is updated.
- A **post** is updated.
- A **custom post type** is updated.

This helps to ensure that visitors always see the latest version of your content.

## Debugging

If the cache clearing fails, the plugin logs messages to the **WordPress error log**.
You can check the log for any issues by accessing your server's error log, typically located at `/wp-content/debug.log` (if WordPress debugging is enabled).

## Security Considerations

- Use an **API Token** instead of the **Global API Key** for security.
- The API Token should have minimal permissions — only the ability to **edit the cache** for specified zones.

## License

This plugin is open-source and distributed under the [MIT License](https://opensource.org/licenses/MIT).

## Contributing

Feel free to fork the repository and submit pull requests. Contributions are welcome to improve the functionality and security of this plugin.

## Support

If you encounter any issues or have questions about using this plugin, please open an issue in the [GitHub repository](https://github.com/your-username/homerunner-cloudflare-cache) or contact us via the support forum.
