# Nerdy SEO - Installation & Setup Guide

## Quick Start

### Installation Steps

1. **Upload to WordPress**
   ```
   Copy the entire `seo-plugin` folder to:
   /wp-content/plugins/nerdy-seo/
   ```

2. **Activate the Plugin**
   - Log into WordPress admin
   - Go to **Plugins > Installed Plugins**
   - Find "Nerdy SEO"
   - Click **Activate**

3. **Configure Basic Settings**
   - Go to **Nerdy SEO > Settings**
   - Set your homepage title and description
   - Configure social media settings
   - Add your Twitter username
   - Upload a default social sharing image
   - Save settings

4. **Start Optimizing Pages**
   - Edit any post or page
   - You'll see three new meta boxes:
     - **Nerdy SEO Settings** (title, description, keywords)
     - **Social Media Preview** (Facebook, Twitter)
     - **Schema Markup** (FAQs, Reviews, etc.)

## Recommended Configuration

### Homepage SEO
1. Go to **Nerdy SEO > Settings**
2. Set **Homepage Title**: Your company name and value proposition
   - Example: "Nerdy South Inc - Expert Web Development & Digital Solutions"
3. Set **Homepage Description**: Brief company description (150-160 characters)
   - Example: "Professional web development, digital marketing, and creative solutions for businesses in the South. Custom WordPress sites, SEO optimization, and more."

### Social Media Settings
1. Enable Open Graph: ✓ (checked)
2. Enable Twitter Cards: ✓ (checked)
3. Twitter Username: @nerdysouthinc
4. Upload a default social image (1200x630px)
   - Use your logo on a branded background
   - This image appears when pages without featured images are shared

### Title Format
Default: `%title% | %sitename%`

Examples:
- Blog posts: "How to Build a Website | Nerdy South Inc"
- Services: "Web Development Services | Nerdy South Inc"
- Custom: `%title% - %sitename% %year%`

## Using with NestedPages

If you have NestedPages installed:

1. Go to **Pages > Nested Pages**
2. You'll see SEO data inline with your pages
3. Click the quick edit icon on any page
4. Edit SEO title and description without opening the full editor
5. Click "Save SEO"

## Migration from AIOSEO

### Option 1: Manual Migration (Recommended for Small Sites)
1. Keep AIOSEO active temporarily
2. Open each important page
3. Copy the title and description from AIOSEO
4. Paste into Nerdy SEO fields
5. Save
6. Deactivate AIOSEO when done

### Option 2: Database Migration (For Large Sites)
Contact the dev team for a migration script that can:
- Map AIOSEO meta fields to Nerdy SEO
- Preserve all titles, descriptions, and social data
- Run as a one-time operation

## Testing Checklist

After installation, test these features:

### Basic SEO
- [ ] Homepage shows custom title and description in source code
- [ ] Blog posts show SEO title in `<title>` tag
- [ ] Meta descriptions appear in `<meta name="description">`
- [ ] Canonical URLs are present

### Social Media
- [ ] Test a Facebook share - correct title, description, image?
- [ ] Test a Twitter share - Twitter Card shows correctly?
- [ ] Open Graph tags present in page source

### Schema Markup
- [ ] Add FAQ schema to a page
- [ ] Test with Google Rich Results Test: https://search.google.com/test/rich-results
- [ ] Verify JSON-LD output in page source

### NestedPages
- [ ] SEO quick edit works
- [ ] Changes save correctly
- [ ] Character counters update in real-time

## Troubleshooting

### Meta tags not showing
1. Check if another SEO plugin is active (Yoast, Rank Math, etc.)
2. Deactivate conflicting plugins
3. Clear your cache (if using a caching plugin)

### NestedPages integration not working
1. Ensure NestedPages plugin is installed and activated
2. Try deactivating and reactivating Nerdy SEO
3. Check browser console for JavaScript errors

### Social sharing not working
1. Verify Open Graph/Twitter Cards are enabled in settings
2. Check page source for meta tags
3. Use Facebook Debugger: https://developers.facebook.com/tools/debug/
4. Use Twitter Card Validator: https://cards-dev.twitter.com/validator

### Schema not appearing
1. Verify Schema is enabled in settings
2. Check that you've selected a schema type in the post
3. For FAQ: ensure questions and answers are filled in
4. For Review: ensure item name and rating are set
5. Test with: https://search.google.com/test/rich-results

## Performance Tips

1. **Use Featured Images**: They're used as fallbacks for social sharing
2. **Write Unique Descriptions**: Don't duplicate meta descriptions across pages
3. **Optimize Image Sizes**: Social images should be 1200x630px but compressed
4. **Use Schema Strategically**: Not every page needs schema - use it where it adds value

## Support Resources

### Testing Tools
- Google Rich Results Test: https://search.google.com/test/rich-results
- Facebook Sharing Debugger: https://developers.facebook.com/tools/debug/
- Twitter Card Validator: https://cards-dev.twitter.com/validator
- Schema.org Documentation: https://schema.org/

### Best Practices
- Title Length: 50-60 characters
- Description Length: 150-160 characters
- Social Images: 1200x630px (Facebook), 1200x675px (Twitter)
- Focus on unique, compelling copy
- Use your focus keyword naturally in title and description

## Next Steps

After installation:

1. Optimize your most important pages first (homepage, key services, top blog posts)
2. Set up FAQ schema on relevant pages
3. Add review schema for testimonials/case studies
4. Monitor Google Search Console for improvements
5. Track ranking changes over time

## Questions?

Contact the Nerdy South Inc development team for support.
