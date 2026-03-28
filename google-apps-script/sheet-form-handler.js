/**
 * Music in the Flesh — Interest Registration Form Handler
 * ========================================================
 * Deploy this as a Google Apps Script Web App to receive form submissions
 * and append them as rows in the active Google Sheet.
 *
 * SETUP INSTRUCTIONS
 * ------------------
 * 1. Open (or create) a Google Sheet to store registrations.
 *    Add a header row: Timestamp | Name | Email
 *
 * 2. In the sheet, go to Extensions → Apps Script.
 *    Delete any existing code and paste this entire file.
 *
 * 3. Click Deploy → New deployment.
 *    - Type: Web app
 *    - Execute as: Me
 *    - Who has access: Anyone
 *    Click Deploy and authorise when prompted.
 *
 * 4. Copy the Web App URL that appears after deployment.
 *    Paste it into events.astro as the FORM_ENDPOINT value.
 *
 * RE-DEPLOYING AFTER CHANGES
 * --------------------------
 * If you edit this script, go to Deploy → Manage deployments,
 * click the pencil icon, set Version to "New version", and Save.
 * The Web App URL stays the same.
 */

const SHEET_NAME = 'Registrations'; // Name of the sheet tab to write to

function doPost(e) {
  const ALLOWED_ORIGIN = 'https://jwaite.com'; // Update if domain changes

  const headers = {
    'Access-Control-Allow-Origin': ALLOWED_ORIGIN,
    'Access-Control-Allow-Methods': 'POST',
    'Content-Type': 'application/json',
  };

  try {
    const data = JSON.parse(e.postData.contents);

    // Honeypot check — bots fill hidden fields, humans don't
    if (data.website) {
      // Silently accept (don't reveal the trap to bots)
      return ContentService
        .createTextOutput(JSON.stringify({ status: 'ok' }))
        .setMimeType(ContentService.MimeType.JSON);
    }

    const name  = (data.name  || '').toString().trim();
    const email = (data.email || '').toString().trim();

    if (!name || !email || !email.includes('@')) {
      return ContentService
        .createTextOutput(JSON.stringify({ status: 'error', message: 'Missing or invalid fields.' }))
        .setMimeType(ContentService.MimeType.JSON);
    }

    const ss    = SpreadsheetApp.getActiveSpreadsheet();
    const sheet = ss.getSheetByName(SHEET_NAME) || ss.getActiveSheet();

    sheet.appendRow([new Date(), name, email]);

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
