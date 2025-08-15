# Advanced-WordPress-Appointments
WordPress Appointment System Plugin is a robust appointment booking plugin designed for physiotherapy and clinical uses. It allows patients to schedule sessions directly from your website, receive email confirmations, and automatically send booking details via WhatsApp to your clinic.
---

## 📌 Description

**Advanced Appointments** is a robust appointment booking plugin designed for physiotherapy and clinical uses.  
It allows patients to schedule sessions directly from your website, receive email confirmations, and automatically send booking details via WhatsApp to your clinic.

This plugin is highly customizable, easy to integrate with Elementor, and includes a fully functional admin dashboard to manage appointments.

---

## ✨ Features

### **Frontend Features**
- 📅 **User-friendly booking form** with name, service, date, time slot, phone, and email fields.  
- 🔄 **Dynamic time slots** with validation for booking date ranges (up to 3 months ahead).  
- 📱 **WhatsApp integration** – patients are redirected to WhatsApp with pre-filled booking details.  
- 📧 **Email notifications**:
  - Sent to admin for every booking.
  - Sent to patients (optional) with appointment details.
- ✅ **Form validation** (phone, email, date restrictions, and required fields).  
- 📱 **Responsive design** for mobile and desktop.

### **Admin Features**
- 📊 **Custom Appointments Dashboard**:
  - View total, today’s, and pending appointments.
  - Recent appointment list with quick edit links.
- 📥 **Export Appointments to CSV**.  
- 🗂 **Custom post type** (`appointment`) for managing bookings.  
- 📝 **Admin meta boxes** for appointment details, status updates, and notes.  
- 🔄 **Status management** – Pending, Confirmed, Completed, Cancelled.  
- 📱 **Quick WhatsApp contact link** from admin.

### **Technical Features**
- 🛡 **Nonce & field sanitization** for security.  
- 🌐 **Translation-ready** (`.pot` file in `/languages`).  
- 🎨 **Customizable styles** in `assets/style.css`.  
- 🧩 **Elementor widget support** – easily add the booking form to any Elementor page.  
- 🚫 **Prevents direct file access** for security.


## 🚀 Installation

**Download the plugin .zip file.**  

**Upload it to your WordPress site:**

**Via Dashboard:**
1. Go to `Plugins → Add New → Upload Plugin`.
2. Select the `.zip` file and click **Install Now**.

**Via FTP:**
1. Upload the extracted folder to `/wp-content/plugins/`.

**Activate the plugin** via `Plugins → Installed Plugins`.

**Add the booking form to any page using:**
```shortcode
[drfarwa_appointment_form]
```
or via the **Elementor widget** `"Dr. Farwa Appointments"`.

---

## ⚙️ Shortcode Options
```shortcode
[drfarwa_appointment_form title="Book Your Appointment"]
```
**Parameters:**
- `title` – Custom heading for the booking form.

---

## 🛠 Configuration
- **Set Admin Email** – The plugin uses the WordPress admin email for notifications.  
- **Edit Styles** – Modify `assets/style.css` to change the form’s appearance.  
- **Change WhatsApp Number** – Update the number in `send_whatsapp_notification()` inside the plugin file.

---

## 📤 CSV Export
1. Go to `Appointments → Dashboard` in the WordPress admin.  
2. Click **Export CSV** to download all appointment data.

---

## 🧩 Elementor Integration
When **Elementor** is active, a widget named `"Dr. Farwa Appointments"` is available.  
Drag it to any page and set a custom form title.

---

## 📚 Changelog

**1.0.1 – Initial Public Release**
- Added appointment booking form with validation.  
- Added email notifications for admin & patients.  
- Built custom admin dashboard with stats & CSV export.  
- Added Elementor widget support.  
- Added responsive design & security improvements.

**1.0.2**
- Implemented WhatsApp integration with patient redirection. 

---

## 📄 License
This plugin is licensed under the **GPL v2 or later**.  
You are free to modify and redistribute under the same license.

---

## 👨‍💻 Author
**Muhammad Hamza Yousaf**  
🔗 [Website](https://github.com/codewithhamza1)
