# hCaptcha Integration Setup Guide

This guide explains how to set up hCaptcha verification for the public record submission form.

## What is hCaptcha?

hCaptcha is a privacy-respecting CAPTCHA alternative that helps protect your form from spam and bot submissions while respecting user privacy.

## Setup Steps

### 1. Get hCaptcha Keys

1. Visit https://www.hcaptcha.com/
2. Sign up for a free account
3. Go to your dashboard and create a new site
4. You'll receive:
   - **Site Key** (public key)
   - **Secret Key** (keep this private)

### 2. Configure Environment Variables

Add the following to your `.env` file in the project root:

```
HCAPTCHA_SITE_KEY=your_site_key_here
HCAPTCHA_SECRET_KEY=your_secret_key_here
```

Replace `your_site_key_here` and `your_secret_key_here` with your actual keys from hCaptcha.

### 3. Verify Setup

The following changes have been made to implement hCaptcha:

#### Frontend Changes:
- **index.html**: Added hCaptcha script tag and widget to the submission form
- **js/script.js**: Added hCaptcha initialization and token capture functionality

#### Backend Changes:
- **auth/config.php**: Added environment variable loading for hCaptcha keys and updated CSP headers
- **php/public_submit.php**: Added hCaptcha verification function and validation check
- **php/get_hcaptcha_sitekey.php**: New endpoint to securely serve the site key to the frontend

## How It Works

1. When the user opens the "Submit Record" form, the hCaptcha widget is initialized with the site key
2. The user must complete the hCaptcha challenge before submitting
3. Upon completion, hCaptcha provides a response token
4. The token is included in the submission and sent to the server
5. The backend verifies the token with hCaptcha's API using the secret key
6. Only valid submissions are processed

## Testing

To test the integration:

1. Ensure your `.env` file has valid hCaptcha keys
2. Open the website and click "Submit Record"
3. You should see an hCaptcha widget
4. Complete the challenge and try submitting a record
5. Check that submissions are working correctly

## Troubleshooting

### hCaptcha widget not appearing
- Verify that `HCAPTCHA_SITE_KEY` is set in your `.env` file
- Check browser console for any JavaScript errors
- Ensure hCaptcha script is loading (check Network tab in dev tools)

### Verification failures
- Verify that `HCAPTCHA_SECRET_KEY` is correct in your `.env` file
- Check server logs for any cURL errors
- Ensure your server can make outbound HTTPS requests to hcaptcha.com

### CSP violations
- The CSP headers have been updated to allow hCaptcha domains
- If you're still seeing CSP errors, check the browser console for the specific violation

## Privacy

hCaptcha is designed with privacy in mind:
- No tracking cookies
- GDPR/CCPA compliant
- Works without storing user data

Learn more at https://www.hcaptcha.com/privacy

## Additional Resources

- [hCaptcha Documentation](https://docs.hcaptcha.com/)
- [hCaptcha Configuration Options](https://docs.hcaptcha.com/configuration)
