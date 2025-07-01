# 📸 Class Images Setup Guide

## ✅ What's Been Implemented

Your "Available Classes" section has been completely redesigned to:

1. **Show only unique class types** (no duplicates)
2. **Display beautiful class cards** with images, age ranges, trial eligibility, and capacity
3. **Provide professional placeholders** with class names and icons when images aren't available
4. **Order classes logically** from youngest to oldest age groups

## 🎯 Current Class Types & Required Images

The system expects these image files in `public/assets/images/classes/`:

| Class Name                 | Image Filename           | Age Range   | Trial Available |
| -------------------------- | ------------------------ | ----------- | --------------- |
| Private 1-1 Tuition        | `private-tuition.jpg`    | 4+ years    | ❌ No           |
| Infants                    | `infants.jpg`            | 4-6 years   | ✅ Yes          |
| Juniors                    | `juniors.jpg`            | 7-11 years  | ✅ Yes          |
| Seniors                    | `seniors.jpg`            | 11-15 years | ✅ Yes          |
| Adult Fundamentals         | `adult-fundamentals.jpg` | 15+ years   | ✅ Yes          |
| Adults Any Level           | `adults-any-level.jpg`   | 15+ years   | ✅ Yes          |
| Adult Advanced             | `adult-advanced.jpg`     | 15+ years   | ❌ No           |
| Adults Bag Work / Open Mat | `adults-bag-work.jpg`    | 15+ years   | ✅ Yes          |
| Adults Sparring            | `adults-sparring.jpg`    | 14+ years   | ❌ No           |

## 📱 How It Currently Looks

- **With Images**: Shows your uploaded photos with hover effects
- **Without Images**: Shows stylish blue gradient placeholders with class names and martial arts icons

## 🔧 How to Add Your Class Photos

### Option 1: Replace Placeholder Files

1. Take or source photos for each class type
2. Resize them to **400x200 pixels** (or similar 2:1 ratio)
3. Save as JPG format with the exact filenames listed above
4. Upload to: `public/assets/images/classes/`

### Option 2: Use Different Filenames

If you want to use different filenames, update the mapping in `public/index.php`:

```php
function getClassImageName($className) {
    $imageMap = [
        'Private 1-1 Tuition' => 'your-private-photo.jpg',
        'Adult Advanced' => 'your-advanced-photo.jpg',
        // ... update other entries
    ];
    return $imageMap[$className] ?? 'default-class.jpg';
}
```

## 📐 Image Specifications

**Recommended Dimensions:**

- **Width**: 400-600px
- **Height**: 200-300px
- **Aspect Ratio**: 2:1 (landscape)
- **Format**: JPG or PNG
- **File Size**: Under 500KB each

**Photo Ideas:**

- **Private 1-1**: Instructor working one-on-one with student
- **Infants**: Young children in training gear, having fun
- **Juniors**: Kids practicing techniques safely
- **Seniors**: Teenagers in focused training
- **Adult Classes**: Adults practicing various Krav Maga techniques
- **Sparring**: Controlled sparring with protective gear
- **Bag Work**: Students working with training bags

## 🎨 Design Features

- **Hover Effects**: Cards lift and images zoom slightly on mouse hover
- **Professional Layout**: Age ranges, capacity, instructor, and trial availability clearly displayed
- **Responsive Design**: Looks great on desktop, tablet, and mobile
- **Consistent Branding**: Uses your site's color scheme and typography

## 🔄 Automatic Updates

The system automatically:

- ✅ Shows real images when files exist
- ✅ Falls back to attractive placeholders when images are missing
- ✅ Displays correct trial eligibility for each class
- ✅ Shows proper age ranges and capacity limits
- ✅ Orders classes from youngest to oldest appropriately

## 🚀 Next Steps

1. **Immediate**: The system works perfectly with placeholders
2. **Soon**: Add your actual class photos using the filenames above
3. **Optional**: Customize the styling in `assets/css/custom.css` if desired

The placeholders look professional, so there's no rush - add photos when convenient!"
