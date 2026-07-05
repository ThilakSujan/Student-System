/**
 * User Model
 * Represents system users: Admin, Staff, and Students (portal access)
 * Merged with user_profiles table (full_name, phone, profile_text)
 */

const mongoose = require('mongoose');
const bcrypt = require('bcryptjs');
const crypto = require('crypto');

const SALT_ROUNDS = 12;

const userSchema = new mongoose.Schema(
  {
    // ─── Core Auth Fields ────────────────────────────────────────────
    username: {
      type: String,
      required: [true, 'Username is required'],
      unique: true,
      trim: true,
      minlength: [3, 'Username must be at least 3 characters'],
      maxlength: [50, 'Username cannot exceed 50 characters'],
    },

    email: {
      type: String,
      required: [true, 'Email is required'],
      unique: true,
      trim: true,
      lowercase: true,
      match: [/^\S+@\S+\.\S+$/, 'Please enter a valid email address'],
    },

    password: {
      type: String,
      required: [true, 'Password is required'],
      minlength: [6, 'Password must be at least 6 characters'],
      select: false, // Never return password in queries by default
    },

    role: {
      type: String,
      enum: {
        values: ['admin', 'staff', 'student'],
        message: 'Role must be admin, staff, or student',
      },
      required: [true, 'Role is required'],
      default: 'staff',
    },

    // ─── OTP / Password Reset Fields ─────────────────────────────────
    reset_otp: {
      type: String,
      select: false, // Hashed OTP — not returned by default
    },

    otp_expiry: {
      type: Date,
      select: false,
    },

    otp_verified: {
      type: Boolean,
      default: false,
    },

    otp_attempts: {
      type: Number,
      default: 0,
      min: 0,
    },

    otp_last_sent: {
      type: Date,
    },

    otp_send_count: {
      type: Number,
      default: 0,
      min: 0,
    },

    // ─── Account Status & Approval ────────────────────────────────────
    account_status: {
      type: String,
      enum: {
        values: ['Pending', 'Approved', 'Rejected', 'Suspended'],
        message: 'Account status must be Pending, Approved, Rejected, or Suspended',
      },
      default: 'Pending',
    },

    approved_by: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'User',
      default: null,
    },

    approved_at: {
      type: Date,
      default: null,
    },

    rejected_by: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'User',
      default: null,
    },

    rejected_at: {
      type: Date,
      default: null,
    },

    rejection_reason: {
      type: String,
      trim: true,
      maxlength: [500, 'Rejection reason cannot exceed 500 characters'],
      default: null,
    },

    // ─── Merged: user_profiles fields ────────────────────────────────
    full_name: {
      type: String,
      trim: true,
      maxlength: [150, 'Full name cannot exceed 150 characters'],
    },

    phone: {
      type: String,
      trim: true,
      match: [/^[+]?[\d\s\-().]{7,20}$/, 'Please enter a valid phone number'],
    },

    profile_text: {
      type: String,
      trim: true,
      maxlength: [1000, 'Profile text cannot exceed 1000 characters'],
    },
  },
  {
    timestamps: true, // Adds createdAt and updatedAt automatically
    toJSON: { virtuals: true },
    toObject: { virtuals: true },
  }
);

// ─── Indexes ──────────────────────────────────────────────────────────────────
userSchema.index({ role: 1 });
userSchema.index({ account_status: 1 });

// ─── Virtuals ─────────────────────────────────────────────────────────────────
/**
 * Virtual: isActive
 * Returns true if the account is Approved
 */
userSchema.virtual('isActive').get(function () {
  return this.account_status === 'Approved';
});

/**
 * Virtual: displayName
 * Returns full_name if available, otherwise falls back to username
 */
userSchema.virtual('displayName').get(function () {
  return this.full_name || this.username;
});

// ─── Pre-Save Hook: Password Hashing ─────────────────────────────────────────
/**
 * Hash password before saving.
 * Only runs if the password field has been modified.
 */
userSchema.pre('save', async function (next) {
  if (!this.isModified('password')) return next();

  try {
    const salt = await bcrypt.genSalt(SALT_ROUNDS);
    this.password = await bcrypt.hash(this.password, salt);
    next();
  } catch (err) {
    next(err);
  }
});

// ─── Instance Methods ─────────────────────────────────────────────────────────

/**
 * matchPassword
 * Compare a plain-text password with the stored hashed password.
 * @param {string} enteredPassword - The plain-text password to verify
 * @returns {Promise<boolean>}
 */
userSchema.methods.matchPassword = async function (enteredPassword) {
  return bcrypt.compare(enteredPassword, this.password);
};

/**
 * generateOTP
 * Generates a 6-digit OTP, hashes it with bcrypt, stores the hash,
 * sets a 10-minute expiry, and increments the send counters.
 * @returns {string} The plain-text OTP (send this to the user via email/SMS)
 */
userSchema.methods.generateOTP = async function () {
  // Generate a cryptographically random 6-digit OTP
  const otp = String(Math.floor(100000 + crypto.randomInt(900000))).padStart(6, '0');

  // Hash the OTP before storing
  const salt = await bcrypt.genSalt(SALT_ROUNDS);
  this.reset_otp = await bcrypt.hash(otp, salt);

  // Set expiry to 10 minutes from now
  this.otp_expiry = new Date(Date.now() + 10 * 60 * 1000);

  // Reset verification state
  this.otp_verified = false;
  this.otp_attempts = 0;

  // Track send metadata
  this.otp_last_sent = new Date();
  this.otp_send_count = (this.otp_send_count || 0) + 1;

  return otp; // Return plain OTP to send to user
};

/**
 * verifyOTP
 * Verifies a plain-text OTP against the stored hash and checks expiry.
 * Increments attempt counter on failure.
 * @param {string} otp - The plain-text OTP entered by the user
 * @returns {{ success: boolean, message: string }}
 */
userSchema.methods.verifyOTP = async function (otp) {
  // Check if OTP exists
  if (!this.reset_otp || !this.otp_expiry) {
    return { success: false, message: 'No OTP has been generated.' };
  }

  // Check expiry
  if (Date.now() > this.otp_expiry.getTime()) {
    return { success: false, message: 'OTP has expired. Please request a new one.' };
  }

  // Check attempt limit (max 5 attempts)
  if (this.otp_attempts >= 5) {
    return { success: false, message: 'Maximum OTP attempts exceeded. Please request a new OTP.' };
  }

  // Compare OTP
  const isMatch = await bcrypt.compare(String(otp), this.reset_otp);

  if (!isMatch) {
    this.otp_attempts += 1;
    return { success: false, message: 'Invalid OTP. Please try again.' };
  }

  // OTP is valid — mark as verified and clear OTP data
  this.otp_verified = true;
  this.otp_attempts = 0;
  this.reset_otp = undefined;
  this.otp_expiry = undefined;

  return { success: true, message: 'OTP verified successfully.' };
};

// ─── Model Export ─────────────────────────────────────────────────────────────
const User = mongoose.model('User', userSchema);

module.exports = User;
