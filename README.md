# Product Images Setup

This directory contains product images for the stock management system.

## ✅ **Database Updated**
The products table now includes an `image` column to store image filenames.

## 🖼️ **Image Display Implementation**
The customer products page now displays actual product images using HTML `<img>` tags.

## 📁 **Directory Structure**
```
assets/
  images/
    README.md                    # This file
    preview.html                 # Preview of how images should look
    airpod_display.html          # Specific AirPod product display
    airpod_placeholder.svg       # Placeholder for AirPod image
    airpod.jpg                   # ← Upload your AirPod image here
    mouse.jpg                    # ← Upload your product images here
    keyboard.jpg                 # ← Upload your product images here
    laptop.jpg                   # ← Upload your product images here
    ...etc
```

## 🔧 **AirPod Image Setup**
For the "Air pod" product to display correctly:

1. **Upload `airpod.jpg`** to this directory
2. **Database entry**: The product "Air pod" has `image = 'airpod.jpg'`
3. **Display**: Customer products page will show the image automatically
4. **Fallback**: If no image, shows a placeholder SVG

## 📤 **General Setup:**

1. **Run Database Update:**
   ```sql
   -- Execute: add_product_images.sql
   ALTER TABLE `products` ADD `image` varchar(255) DEFAULT NULL;
   UPDATE `products` SET `image` = 'airpod.jpg' WHERE `name` = 'Air pod';
   -- ... etc for all products
   ```

2. **Upload Images:**
   - Place product images in `assets/images/` directory
   - Use filenames that match the database entries
   - Supported formats: JPG, PNG, GIF, WebP

3. **Test:**
   - Visit customer products page
   - Images should display automatically
   - Fallback to placeholder for missing images

## 🎯 **Features:**
- ✅ **Real product images** instead of icons
- ✅ **Responsive design** with hover effects
- ✅ **AirPod image support** with placeholder
- ✅ **Admin image management** with upload/delete
- ✅ **File validation** and security
- ✅ **Fallback system** for missing images
- ✅ **SEO optimized** with alt tags

**Special Note for AirPod:** The "Air pod" product is configured to use `airpod.jpg`. Upload your AirPod image to see it displayed as a product cover in the customer products page!