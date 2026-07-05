const nodemailer = require('nodemailer');
const logger = require('../utils/logger');
const EmailLog = require('../models/EmailLog');

// Create Nodemailer transporter
const transporter = nodemailer.createTransport({
  host: process.env.SMTP_HOST || 'smtp.mailtrap.io',
  port: process.env.SMTP_PORT || 2525,
  auth: {
    user: process.env.SMTP_USER,
    pass: process.env.SMTP_PASS,
  },
});

// Helper to log emails
const logEmail = async (logData) => {
  try {
    await EmailLog.create(logData);
  } catch (error) {
    logger.error('Failed to log email:', error);
  }
};

// Base HTML Template
const getHtmlTemplate = (title, content) => `
<!DOCTYPE html>
<html>
<head>
  <style>
    body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #0f172a; margin: 0; padding: 20px; }
    .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    .header { background-color: #1e293b; padding: 24px; text-align: center; color: #ffffff; }
    .header h1 { margin: 0; font-size: 20px; font-weight: 600; }
    .content { padding: 32px 24px; }
    .footer { background-color: #f1f5f9; padding: 16px; text-align: center; font-size: 12px; color: #64748b; }
    .button { display: inline-block; padding: 12px 24px; background-color: #4f46e5; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 500; margin-top: 16px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Tech Vision Educational Institute</h1>
    </div>
    <div class="content">
      <h2 style="font-size: 18px; margin-top: 0;">${title}</h2>
      ${content}
    </div>
    <div class="footer">
      This is an automated message from the Student Management System. Please do not reply.
    </div>
  </div>
</body>
</html>
`;

exports.sendRegistrationEmail = async (email, username) => {
  const content = `
    <p>Dear ${username},</p>
    <p>Your registration request has been received. Our administrators will review your account shortly.</p>
    <p>You will receive another email once your account is approved.</p>
  `;
  const subject = 'Registration Received';
  
  try {
    await transporter.sendMail({
      from: `"Tech Vision" <${process.env.SMTP_FROM || 'noreply@techvision.edu'}>`,
      to: email,
      subject,
      html: getHtmlTemplate(subject, content),
    });
    await logEmail({ recipient_email: email, subject, email_type: 'custom', status: 'sent', created_by: null });
  } catch (error) {
    await logEmail({ recipient_email: email, subject, email_type: 'custom', status: 'failed', error_message: error.message });
  }
};

exports.sendAccountApproved = async (email, username) => {
  const content = `
    <p>Dear ${username},</p>
    <p>Great news! Your account has been approved by the administrator.</p>
    <p>You can now log in to the portal using your credentials.</p>
    <a href="${process.env.FRONTEND_URL || 'http://localhost:5173'}/login" class="button">Log In Now</a>
  `;
  const subject = 'Account Approved';
  
  try {
    await transporter.sendMail({
      from: `"Tech Vision" <${process.env.SMTP_FROM}>`,
      to: email,
      subject,
      html: getHtmlTemplate(subject, content),
    });
    await logEmail({ recipient_email: email, subject, email_type: 'custom', status: 'sent' });
  } catch (error) {
    await logEmail({ recipient_email: email, subject, email_type: 'custom', status: 'failed', error_message: error.message });
  }
};

exports.sendAccountRejected = async (email, username, reason) => {
  const content = `
    <p>Dear ${username},</p>
    <p>We regret to inform you that your account registration has been rejected.</p>
    <p><strong>Reason:</strong> ${reason}</p>
    <p>If you believe this is a mistake, please contact support.</p>
  `;
  const subject = 'Account Registration Update';
  
  try {
    await transporter.sendMail({
      from: `"Tech Vision" <${process.env.SMTP_FROM}>`,
      to: email,
      subject,
      html: getHtmlTemplate(subject, content),
    });
    await logEmail({ recipient_email: email, subject, email_type: 'custom', status: 'sent' });
  } catch (error) {
    await logEmail({ recipient_email: email, subject, email_type: 'custom', status: 'failed', error_message: error.message });
  }
};

exports.sendOtpEmail = async (email, username, otp) => {
  const content = `
    <p>Dear ${username},</p>
    <p>We received a request to reset your password. Here is your One-Time Password (OTP):</p>
    <div style="background-color: #f1f5f9; padding: 16px; text-align: center; border-radius: 6px; margin: 24px 0;">
      <span style="font-size: 32px; font-weight: 700; letter-spacing: 4px; color: #0f172a;">${otp}</span>
    </div>
    <p>This code will expire in 10 minutes. If you did not request this, please ignore this email.</p>
  `;
  const subject = 'Password Reset OTP';
  
  try {
    await transporter.sendMail({
      from: `"Tech Vision" <${process.env.SMTP_FROM}>`,
      to: email,
      subject,
      html: getHtmlTemplate(subject, content),
    });
    await logEmail({ recipient_email: email, subject, email_type: 'custom', status: 'sent' });
  } catch (error) {
    await logEmail({ recipient_email: email, subject, email_type: 'custom', status: 'failed', error_message: error.message });
  }
};

exports.sendAttendanceAlert = async (studentEmail, studentName, date, status) => {
  const content = `
    <p>Dear ${studentName},</p>
    <p>This is to inform you that you have been marked as <strong>${status}</strong> for your classes on ${date}.</p>
    <p>Please log in to the portal to view your complete attendance record.</p>
  `;
  const subject = 'Attendance Update';
  
  try {
    await transporter.sendMail({
      from: `"Tech Vision" <${process.env.SMTP_FROM}>`,
      to: studentEmail,
      subject,
      html: getHtmlTemplate(subject, content),
    });
    await logEmail({ recipient_email: studentEmail, subject, email_type: 'attendance', status: 'sent' });
  } catch (error) {
    await logEmail({ recipient_email: studentEmail, subject, email_type: 'attendance', status: 'failed', error_message: error.message });
  }
};

exports.sendMarksPublished = async (studentEmail, studentName, marks) => {
  const content = `
    <p>Dear ${studentName},</p>
    <p>New exam marks have been published to your profile.</p>
    <p>Please log in to the student portal to view your results and download your report card.</p>
    <a href="${process.env.FRONTEND_URL || 'http://localhost:5173'}/marks" class="button">View Marks</a>
  `;
  const subject = 'New Marks Published';
  
  try {
    await transporter.sendMail({
      from: `"Tech Vision" <${process.env.SMTP_FROM}>`,
      to: studentEmail,
      subject,
      html: getHtmlTemplate(subject, content),
    });
    await logEmail({ recipient_email: studentEmail, subject, email_type: 'marks_published', status: 'sent' });
  } catch (error) {
    await logEmail({ recipient_email: studentEmail, subject, email_type: 'marks_published', status: 'failed', error_message: error.message });
  }
};

exports.sendCustomEmail = async (to, subject, htmlBody) => {
  try {
    await transporter.sendMail({
      from: `"Tech Vision" <${process.env.SMTP_FROM}>`,
      to,
      subject,
      html: getHtmlTemplate(subject, htmlBody),
    });
    await logEmail({ recipient_email: to, subject, email_type: 'custom', status: 'sent' });
  } catch (error) {
    await logEmail({ recipient_email: to, subject, email_type: 'custom', status: 'failed', error_message: error.message });
  }
};
