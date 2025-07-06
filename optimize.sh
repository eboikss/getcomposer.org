#!/bin/bash

# Image Optimization Script for Composer Website

echo "Starting image optimization..."

# Create optimized directory
mkdir -p web/img/optimized

# Optimize PNG logos (reduce to 60% quality and optimize)
for file in web/img/logo-composer-transparent*.png; do
    if [ -f "$file" ]; then
        filename=$(basename "$file")
        echo "Optimizing $filename..."
        
        # Convert to WebP for modern browsers (much smaller)
        convert "$file" -quality 85 "web/img/optimized/${filename%.png}.webp"
        
        # Optimize original PNG
        convert "$file" -strip -interlace Plane -quality 85 "web/img/optimized/$filename"
        optipng -o5 "web/img/optimized/$filename"
        
        # Get file sizes for comparison
        original_size=$(du -h "$file" | cut -f1)
        webp_size=$(du -h "web/img/optimized/${filename%.png}.webp" | cut -f1)
        png_size=$(du -h "web/img/optimized/$filename" | cut -f1)
        
        echo "  Original: $original_size → WebP: $webp_size, PNG: $png_size"
    fi
done

# Optimize other images
for file in web/img/*.png web/img/*.jpg web/img/*.gif; do
    if [ -f "$file" ] && [[ ! "$file" =~ logo-composer-transparent ]]; then
        filename=$(basename "$file")
        echo "Optimizing $filename..."
        
        case "${file##*.}" in
            png)
                cp "$file" "web/img/optimized/$filename"
                optipng -o5 "web/img/optimized/$filename"
                ;;
            jpg|jpeg)
                convert "$file" -strip -interlace Plane -quality 85 "web/img/optimized/$filename"
                ;;
            gif)
                cp "$file" "web/img/optimized/$filename"
                ;;
        esac
    fi
done

echo "Image optimization complete!"
echo "Optimized images are in web/img/optimized/"