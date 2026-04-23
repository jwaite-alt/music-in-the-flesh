/**
 * Music in the Flesh — Form Handler
 * ===================================
 * Handles two form types:
 *   type: 'registration' — interest registration from Upcoming Events page
 *   type: 'contact'      — contact / message from Contact page
 *
 * SETUP INSTRUCTIONS
 * ------------------
 * 1. Open the Google Sheet used for registrations.
 *    Ensure it has these sheet tabs (create if missing):
 *      - Registrations  → Timestamp | Name | Email  (shared by Upcoming Events sign-ups and Contact updates opt-ins)
 *      - Contact        → Timestamp | Name | Email | Message | Updates opt-in
 *
 * 2. In the sheet, go to Extensions → Apps Script.
 *    Delete any existing code and paste this entire file.
 *
 * 3. Click Deploy → New deployment (or update existing deployment).
 *    - Type: Web app
 *    - Execute as: Me
 *    - Who has access: Anyone
 *    Click Deploy and authorise when prompted.
 *
 * 4. The Web App URL stays the same if updating an existing deployment —
 *    just set Version to "New version" when saving.
 *
 * RE-DEPLOYING AFTER CHANGES
 * --------------------------
 * Deploy → Manage deployments → pencil icon → Version: New version → Save.
 */

function doPost(e) {
  const ALLOWED_ORIGIN = 'https://musicintheflesh.org';

  const headers = {
    'Access-Control-Allow-Origin': ALLOWED_ORIGIN,
    'Access-Control-Allow-Methods': 'POST',
    'Content-Type': 'application/json',
  };

  try {
    const data = JSON.parse(e.postData.contents);

    // Honeypot — bots fill hidden fields, humans don't
    if (data.website) {
      return ContentService
        .createTextOutput(JSON.stringify({ status: 'ok' }))
        .setMimeType(ContentService.MimeType.JSON);
    }

    const type  = (data.type  || 'registration').toString().trim();
    const name  = (data.name  || '').toString().trim();
    const email = (data.email || '').toString().trim();

    if (!name || !email || !email.includes('@')) {
      return ContentService
        .createTextOutput(JSON.stringify({ status: 'error', message: 'Missing or invalid fields.' }))
        .setMimeType(ContentService.MimeType.JSON);
    }

    const ss = SpreadsheetApp.getActiveSpreadsheet();

    if (type === 'contact') {
      const message = (data.message || '').toString().trim();
      const updates = data.updates ? 'Yes' : 'No';

      if (!message) {
        return ContentService
          .createTextOutput(JSON.stringify({ status: 'error', message: 'Message is required.' }))
          .setMimeType(ContentService.MimeType.JSON);
      }

      // Write to Contact sheet
      const contactSheet = ss.getSheetByName('Contact') || ss.insertSheet('Contact');
      if (contactSheet.getLastRow() === 0) {
        contactSheet.appendRow(['Timestamp', 'Name', 'Email', 'Message', 'Updates opt-in']);
      }
      contactSheet.appendRow([new Date(), name, email, message, updates]);

      // If opted in to updates, also write to Registrations sheet
      if (data.updates) {
        const registrationsSheet = ss.getSheetByName('Registrations') || ss.getActiveSheet();
        registrationsSheet.appendRow([new Date(), name, email]);
      }

    } else {
      // Default: registration from Upcoming Events page
      const registrationsSheet = ss.getSheetByName('Registrations') || ss.getActiveSheet();
      registrationsSheet.appendRow([new Date(), name, email]);
    }

    return ContentService
      .createTextOutput(JSON.stringify({ status: 'ok' }))
      .setMimeType(ContentService.MimeType.JSON);

  } catch (err) {
    return ContentService
      .createTextOutput(JSON.stringify({ status: 'error', message: err.message }))
      .setMimeType(ContentService.MimeType.JSON);
  }
}

// Required for CORS preflight
function doGet() {
  return ContentService
    .createTextOutput(JSON.stringify({ status: 'ok' }))
    .setMimeType(ContentService.MimeType.JSON);
}
