# Nerdy SEO - Free WordPress SEO Plugin

**A lightweight, powerful, and completely free WordPress SEO plugin built by digital marketers who got tired of overpaying for bloated software.**

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![WordPress](https://img.shields.io/badge/WordPress-5.5%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://www.php.net/)

---

## Why We Built This

As a digital marketing agency, we were spending **thousands of dollars per year** on premium SEO plugins across our client sites. After years of using these "enterprise" solutions, we realized something frustrating:

- 🎯 **90% of features go unused** - Most sites only need titles, meta descriptions, schema, and social tags
- 💸 **Expensive recurring costs** - $200-500/year per site adds up fast when you manage dozens of clients
- 🐌 **Performance overhead** - Premium plugins are bloated with features nobody asked for
- 🔒 **Vendor lock-in** - Switching plugins means losing data and spending hours on migration

**SEO isn't rocket science.** At its core, you need:
- Clean title tags and meta descriptions
- Proper schema markup for rich snippets
- Open Graph and Twitter Cards for social sharing
- XML sitemaps for search engines
- A few technical features like redirects and robots.txt

That's it. You don't need AI content generators, keyword density analyzers, or 47 different dashboard widgets telling you to "optimize" things that don't matter.

So we built **Nerdy SEO** - a plugin that does what SEO plugins should have been doing all along: **the essentials, done right, completely free.**

---

## Features

### 🎯 Core SEO Features

**Title & Meta Management**
- Full control over page titles with template variables (`%title%`, `%sitename%`, `%separator%`, etc.)
- Meta descriptions with real-time Google search preview
- Canonical URL support
- Per-post and global settings
- Content type templates (posts, pages, custom post types)

**Schema Markup (JSON-LD)**
- Automatic Article schema for blog posts
- Automatic WebPage schema for pages
- LocalBusiness schema with full address, hours, and social profiles
- FAQ schema with Q&A builder
- Review schema with ratings
- Global schema templates (add Product, Organization, or any schema to every page)
- Clean, valid JSON-LD output

**Social Media Integration**
- Open Graph tags for Facebook, LinkedIn
- Twitter Cards with card type selection
- Custom social images per post
- Fallback images for posts without featured images
- Social profile URLs

### 🛠️ Technical Features

**XML Sitemaps**
- Automatic sitemap generation
- Includes posts, pages, and custom post types
- Proper priority and change frequency
- Excludes noindexed content

**301/302 Redirects**
- Full redirect management interface
- Pattern matching support
- Redirect logging and statistics
- Import/export functionality

**Local Business SEO**
- Complete NAP (Name, Address, Phone) management
- Business hours with day-by-day scheduling
- GPS coordinates for accurate mapping
- Multiple business types (Restaurant, Medical, Legal, etc.)
- Social media profile integration
- Outputs proper LocalBusiness schema

**404 Monitoring**
- Log 404 errors automatically
- Track frequency and user agents
- Quick redirect creation from 404 log
- Helps identify broken links

**Image SEO**
- Bulk alt text editor
- Find images missing alt text
- SEO-friendly filename suggestions

**Migration Tools**
- One-click migration from All in One SEO (AIOSEO)
- Preserves all meta data, titles, descriptions
- Converts AIOSEO variables to Nerdy SEO format
- Migrates local business settings, social profiles, and global settings
- Non-destructive (keeps AIOSEO data intact)

**Breadcrumbs**
- Automatic breadcrumb generation for all page types
- BreadcrumbList schema markup for search engines
- Customizable separator and home text
- Template function and shortcode support
- Respects page hierarchy and taxonomy structure

**Robots.txt Editor**
- Virtual robots.txt file management
- Live editor with syntax highlighting
- Common examples and templates
- Reset to default with one click
- Automatic sitemap reference
- Blocks WordPress admin areas by default

### 🎨 User Experience

**Variable Insertion System**
- Click-to-insert variable buttons (no more memorizing syntax)
- Expandable variable reference
- Works on titles, descriptions, and templates
- Real-time preview as you type

**Google Search Preview**
- See exactly how your pages will look in Google search results
- Live updates as you type
- Shows actual site favicon
- Works on homepage, content types, and individual posts

**Clean, Modern Interface**
- Tabbed settings for organization
- WordPress admin design language
- No bloat, no upsells, no upgrade nags
- Mobile-responsive

**NestedPages Integration**
- Edit SEO data directly in NestedPages interface
- Quick edit for titles and descriptions
- Seamless workflow for page management

---

## Installation

### From GitHub

1. Download the latest release or clone this repository:
```bash
git clone https://github.com/yourusername/nerdy-seo.git
```

2. Upload the `nerdy-seo` folder to `/wp-content/plugins/`

3. Activate the plugin through the 'Plugins' menu in WordPress

### Requirements

- WordPress 5.5 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

---

## Getting Started

### Basic Setup (5 minutes)

1. **Configure Homepage SEO**
   - Navigate to **Nerdy SEO → Settings → General**
   - Set your homepage title and meta description
   - Use variables like `%sitename%` and `%separator%` for dynamic content

2. **Set Up Content Types**
   - Go to **Nerdy SEO → Settings → Content Types**
   - Configure default title/description templates for posts and pages
   - Toggle search visibility for each post type

3. **Enable Schema Markup**
   - Visit **Nerdy SEO → Settings → Schema**
   - Ensure schema is enabled
   - (Optional) Add global schema that appears on every page

4. **Configure Social Media**
   - Go to **Nerdy SEO → Settings → Social Media**
   - Enable Open Graph and Twitter Cards
   - Upload a default social sharing image

### Local Business Setup (Optional)

If you're a local business, restaurant, law firm, medical practice, etc.:

1. Go to **Nerdy SEO → Local Business**
2. Enable Local Business schema
3. Fill in your business information:
   - Name, type, description
   - Full address and phone
   - Business hours
   - GPS coordinates (optional but recommended)
   - Social media profiles

Your LocalBusiness schema will automatically appear on every page, helping you show up in Google's local pack and map results.

### Migrating from AIOSEO

1. Go to **Nerdy SEO → Migration**
2. Review what data will be migrated
3. Click "Start Migration"
4. Wait for completion (usually takes 10-30 seconds)
5. Verify a few pages to ensure data migrated correctly
6. Deactivate AIOSEO (or keep it as a backup)

**What gets migrated:**
- SEO titles and meta descriptions
- Focus keywords
- Canonical URLs
- Robots meta (noindex, nofollow)
- Open Graph data
- Twitter Card data
- Schema settings
- Local Business information (name, address, hours, social profiles)
- Global settings (separator, default images)

---

## Usage Examples

### Using Template Variables

Create dynamic, scalable SEO templates using variables:

```
Title: %title% %separator% %sitename%
Description: %excerpt% Learn more at %sitename%.
```

**Available Variables:**
- `%title%` - Post/page title
- `%sitename%` - Site name
- `%sitedesc%` - Site tagline
- `%separator%` - Title separator (configurable)
- `%excerpt%` - Post excerpt
- `%author%` - Post author name
- `%date%` - Publish date
- `%year%` - Current year
- `%month%` - Current month
- `%day%` - Current day
- `%categories%` - Post categories
- `%tags%` - Post tags

### Adding Global Schema

Want a Product or Organization schema on every page?

1. Go to **Settings → Schema → Global Schema Templates**
2. Add your JSON (without `@context` or `@graph`):

```json
{
  "@type": "Product",
  "name": "SEO Services",
  "description": "Professional SEO services for small businesses",
  "offers": {
    "@type": "Offer",
    "price": "1500",
    "priceCurrency": "USD"
  }
}
```

3. Save - it now appears on every page alongside page-specific schema

### Creating Redirects

1. Go to **Nerdy SEO → Redirects**
2. Click "Add New Redirect"
3. Enter source URL: `/old-page`
4. Enter destination: `/new-page`
5. Choose 301 (permanent) or 302 (temporary)
6. Save

**Pattern Matching:**
- Use `*` as wildcard: `/blog/*` → `/articles/*`
- Regex support for advanced matching

### Using Breadcrumbs

**In Your Theme:**
Add this code to your theme's template files (like single.php, page.php):
```php
<?php
if (function_exists('nerdy_seo_breadcrumbs')) {
    nerdy_seo_breadcrumbs();
}
?>
```

**Using Shortcode:**
Add `[nerdy_breadcrumbs]` to any post or page content.

**Customization:**
Go to **Nerdy SEO → Settings → Breadcrumbs** to:
- Enable/disable breadcrumbs
- Choose separator (›, /, », etc.)
- Customize home text
- Toggle BreadcrumbList schema output

### Managing Robots.txt

1. Go to **Nerdy SEO → Robots.txt**
2. Enable custom robots.txt
3. Edit the rules in the text editor:
   - `User-agent:` - Specify which bots the rules apply to
   - `Disallow:` - Block access to specific paths
   - `Allow:` - Permit access to specific paths
   - `Sitemap:` - Reference your XML sitemap
   - `Crawl-delay:` - Set delay between bot requests

**Common Use Cases:**
- Block specific directories (uploads, private folders)
- Block specific bots (scrapers, bad actors)
- Set crawl delays to reduce server load
- Reference sitemaps for search engines

**Default Configuration:**
Nerdy SEO blocks WordPress admin areas and sensitive files by default while allowing admin-ajax.php for functionality. You can reset to these defaults anytime.

**Important:** If a physical `robots.txt` file exists in your WordPress root, it will override the virtual robots.txt.

### Per-Page Schema

Edit any post or page and scroll to the **Schema Markup** meta box:

- Select schema type (Article, WebPage, FAQ, Review, etc.)
- Fill in schema-specific fields
- For FAQ: Add unlimited Q&A pairs
- For Review: Set rating, reviewer, item being reviewed

---

## Technical Details

### Schema Output

Nerdy SEO outputs clean, valid JSON-LD structured data:

```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Article",
      "headline": "Your Post Title",
      "datePublished": "2025-01-10T12:00:00+00:00",
      "author": {
        "@type": "Person",
        "name": "Author Name"
      }
    },
    {
      "@type": "LocalBusiness",
      "name": "Your Business",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "123 Main St",
        "addressLocality": "City",
        "addressRegion": "State",
        "postalCode": "12345"
      }
    }
  ]
}
```

### Database Tables

Nerdy SEO creates minimal database tables:

- `wp_nerdy_seo_redirects` - Stores redirect rules
- `wp_nerdy_seo_404_log` - Logs 404 errors (optional)

All post-level data is stored as WordPress post meta (no custom tables needed).

### Performance

- **No frontend JavaScript** - Zero JS bloat
- **Minimal CSS** - Admin styles only
- **Efficient queries** - Uses WordPress caching
- **Clean code** - Follows WordPress coding standards
- **No tracking** - We don't phone home or collect analytics

---

## Comparison with Premium Plugins

| Feature | Nerdy SEO | AIOSEO Pro | Yoast Premium | RankMath Pro |
|---------|-----------|------------|---------------|--------------|
| Price | **Free** | $199/year | $99/year | $59/year |
| Title & Meta | ✅ | ✅ | ✅ | ✅ |
| Schema Markup | ✅ | ✅ | ✅ | ✅ |
| Local Business | ✅ | ✅ | ✅ | ✅ |
| Redirects | ✅ | ✅ | ✅ | ✅ |
| XML Sitemaps | ✅ | ✅ | ✅ | ✅ |
| Migration Tools | ✅ (AIOSEO) | ❌ | ❌ | ✅ |
| Google Search Preview | ✅ | ✅ | ✅ | ✅ |
| Content Analysis | ❌ | ✅ | ✅ | ✅ |
| Keyword Tracking | ❌ | ✅ | ✅ | ✅ |
| Link Assistant | ❌ | ✅ | ✅ | ✅ |
| AI Features | ❌ | ✅ | ❌ | ✅ |
| Dashboard Bloat | ❌ | ✅ | ✅ | ✅ |
| Upsell Notifications | ❌ | ✅ | ✅ | ✅ |

**Our Philosophy:** We focus on features that actually impact SEO. Content analysis, keyword density, and readability scores are marketing gimmicks. Write for humans, optimize the technical stuff, and you'll rank.

---

## Roadmap

We're actively developing Nerdy SEO. Here's what's coming:

**Near Term:**
- [x] Breadcrumb output and customization
- [ ] WooCommerce product schema enhancement
- [x] Robots.txt editor
- [ ] .htaccess editor
- [ ] Import/export settings

**Future:**
- [ ] Migration from Yoast and RankMath
- [ ] Link building tools (internal linking suggestions)
- [ ] Structured data testing integration
- [ ] Google Search Console integration
- [ ] Advanced schema types (Event, Recipe, Video)

**Not Planned:**
- ❌ AI content writing (use ChatGPT)
- ❌ Keyword density analysis (outdated)
- ❌ Readability scores (write for your audience)
- ❌ Premium/Pro version (we're staying free)

---

## Contributing

We welcome contributions! Here's how you can help:

### Reporting Bugs

Found a bug? Please [open an issue](https://github.com/yourusername/nerdy-seo/issues) with:
- WordPress version
- PHP version
- Theme name
- Steps to reproduce
- Expected vs actual behavior

### Suggesting Features

Have an idea? Open an issue with:
- Use case (why you need it)
- How it would work
- Whether you'd use it regularly

**Please note:** We're selective about feature additions. We prioritize features that:
- Directly impact SEO performance
- Are used by most sites (not edge cases)
- Don't add bloat or complexity

### Code Contributions

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Follow WordPress coding standards
5. Test thoroughly
6. Submit a pull request

**Coding Guidelines:**
- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Add inline documentation for complex logic
- Keep functions focused and modular
- No tracking, analytics, or external API calls
- Maintain backwards compatibility

---

## Support

### Documentation

Full documentation is available in the `/docs` folder and on our wiki.

### Community Support

- **GitHub Issues:** Bug reports and feature requests
- **Discussions:** General questions and tips

### Professional Support

Need help with implementation or have custom requirements? Contact us:
- **Email:** support@nerdysouthinc.com
- **Website:** [https://nerdysouthinc.com](https://nerdysouthinc.com)

---

## Frequently Asked Questions

### Is this really free?

Yes. Completely free. No premium version, no upsells, no hidden costs.

### Why isn't there a Pro version?

We believe core SEO features should be accessible to everyone. The features in Nerdy SEO are all you need for proper technical SEO. Adding a "Pro" version would mean artificially limiting the free version or adding bloat nobody needs.

### Can I use this on client sites?

Absolutely! We built this specifically for agencies. Use it on unlimited sites.

### Will this slow down my site?

No. Nerdy SEO is lightweight and follows WordPress best practices. Most features only run in wp-admin, and frontend output is minimal (just meta tags and schema).

### Can I migrate from [other plugin]?

Currently we support AIOSEO migration. Yoast and RankMath migration tools are planned.

### What about breadcrumbs?

Yes! Breadcrumbs are fully implemented. Use the `nerdy_seo_breadcrumbs()` template function or the `[nerdy_breadcrumbs]` shortcode. Includes automatic BreadcrumbList schema markup.

### Does this work with page builders?

Yes. Nerdy SEO works with Gutenberg, Elementor, Divi, Beaver Builder, and any other page builder.

### What about WooCommerce?

Basic WooCommerce product schema is included. We're working on enhanced product schema features.

### Can I remove the "Generated with Nerdy SEO" comment?

Sure, though we'd appreciate if you kept it! Look for it in `/includes/class-frontend.php` if you want to remove it.

---

## Credits

**Developed by:** [Nerdy South Inc](https://nerdysouthinc.com)
**Contributors:** See [CONTRIBUTORS.md](CONTRIBUTORS.md)

Built with:
- WordPress Plugin API
- Schema.org standards
- Open Graph Protocol
- Twitter Cards specification

---

## License

Nerdy SEO is licensed under the GNU General Public License v2.0 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

See [LICENSE](LICENSE) for full details.

---

## Final Thoughts

**SEO plugins shouldn't cost hundreds per year.**

The core of SEO is simple: proper HTML markup, clean structured data, and fast page loads. Everything else is noise.

We built Nerdy SEO because we were tired of paying for features we didn't need, dealing with bloated dashboards full of "suggestions" that don't move the needle, and watching our sites slow down under the weight of premium plugins.

If you're tired of the same thing, give Nerdy SEO a try. It does what matters, does it well, and stays out of your way.

And if you find it useful, star the repo, tell your friends, and contribute if you can. Let's build something better together.

**Now go rank some pages.** 🚀

---

## Links

- [GitHub Repository](https://github.com/yourusername/nerdy-seo)
- [Issue Tracker](https://github.com/yourusername/nerdy-seo/issues)
- [Changelog](CHANGELOG.md)
- [Nerdy South Inc](https://nerdysouthinc.com)
