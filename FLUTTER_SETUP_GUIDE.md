# Flutter Setup Guide for Khan Invoice Android App

## Prerequisites Installation (Windows)

### Step 1: Install Flutter SDK

**Download Flutter:**
1. Visit: https://docs.flutter.dev/get-started/install/windows
2. Download Flutter SDK (latest stable version)
3. Extract to `C:\src\flutter` (recommended location)

**Add to PATH:**
1. Search "Environment Variables" in Windows Start Menu
2. Click "Environment Variables"
3. Under "User variables", find "Path" and click "Edit"
4. Click "New" and add: `C:\src\flutter\bin`
5. Click "OK" to save

**Verify Installation:**
```bash
flutter --version
flutter doctor
```

### Step 2: Install Android Studio

**Download & Install:**
1. Visit: https://developer.android.com/studio
2. Download Android Studio (latest version)
3. Run installer with default settings
4. Complete the setup wizard

**Install Required Components:**
During setup, ensure these are installed:
- Android SDK
- Android SDK Platform-Tools
- Android SDK Build-Tools
- Android Emulator

### Step 3: Accept Android Licenses

```bash
flutter doctor --android-licenses
```
Type "y" to accept all licenses

### Step 4: Verify Setup

```bash
flutter doctor -v
```

Expected output should show:
- ✅ Flutter (Channel stable)
- ✅ Android toolchain - develop for Android devices
- ✅ Android Studio

---

## Quick Setup (If You Already Have Flutter)

If Flutter is already installed elsewhere on your system:

```bash
# Navigate to parent directory
cd C:\Users\yomi

# Create Flutter project
flutter create --org com.khaninvoice --platforms android khan_invoice_app

# Navigate to project
cd khan_invoice_app

# Check everything is working
flutter doctor
flutter pub get

# Run app (with emulator or device connected)
flutter run
```

---

## Alternative: Use Pre-built APK Approach

If you want to skip local development and just build the app:

### Option A: Use GitHub Actions for Building
1. Push code to GitHub
2. Set up GitHub Actions workflow
3. Build APK in the cloud
4. Download and test on device

### Option B: Use AppFlow or Codemagic
1. Connect your git repository
2. Configure build settings
3. Build APK remotely
4. Download release

---

## Next Steps After Installation

Once Flutter is installed, run:
```bash
cd C:\Users\yomi
flutter create --org com.khaninvoice --platforms android khan_invoice_app
```

Then I'll help you:
1. Configure the project structure
2. Add dependencies (dio, provider, shared_preferences)
3. Build the API service layer
4. Create authentication screens
5. Implement dashboard and features

---

## Estimated Time

- **Flutter + Android Studio Installation:** 30-60 minutes
- **Project Setup & Configuration:** 15 minutes
- **Building Core Features:** 4-5 weeks (following our plan)

---

## System Requirements

**Minimum:**
- Windows 10 or later (64-bit)
- 8GB RAM (16GB recommended)
- 10GB free disk space (for SDK + Android Studio)
- Git for Windows

**Recommended:**
- Windows 11
- 16GB+ RAM
- SSD with 20GB+ free space

---

## Need Help?

Common issues and solutions:

**Issue: "cmdline-tools component is missing"**
```bash
# Open Android Studio
# Tools > SDK Manager > SDK Tools
# Check "Android SDK Command-line Tools"
# Click Apply
```

**Issue: "Unable to locate Android SDK"**
```bash
# Set ANDROID_HOME environment variable
# Path: C:\Users\YourName\AppData\Local\Android\Sdk
```

**Issue: "No connected devices"**
```bash
# Option 1: Use Android Emulator
# - Open Android Studio > Device Manager > Create Virtual Device

# Option 2: Use Physical Device
# - Enable Developer Options on phone
# - Enable USB Debugging
# - Connect via USB cable
```

---

## Ready to Continue?

After installing Flutter, let me know and I'll:
1. Create the Khan Invoice Flutter project
2. Set up the complete folder structure
3. Add all required dependencies
4. Build the authentication flow
5. Connect to the Laravel API we built

The backend API is ready and waiting at http://127.0.0.1:8000/api/v1 🚀
