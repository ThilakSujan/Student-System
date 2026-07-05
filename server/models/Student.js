/**
 * Student Model
 * Represents enrolled students in the institution.
 * Includes password field for MERN first-login setup and isFirstLogin flag.
 */

const mongoose = require('mongoose');
const bcrypt = require('bcryptjs');

const SALT_ROUNDS = 12;

const studentSchema = new mongoose.Schema(
  {
    // ─── Personal Information ─────────────────────────────────────────
    student_name: {
      type: String,
      required: [true, 'Student name is required'],
      trim: true,
      maxlength: [150, 'Student name cannot exceed 150 characters'],
    },

    email: {
      type: String,
      required: [true, 'Student email is required'],
      unique: true,
      trim: true,
      lowercase: true,
      match: [/^\S+@\S+\.\S+$/, 'Please enter a valid email address'],
    },

    phone: {
      type: String,
      trim: true,
      match: [/^[+]?[\d\s\-().]{7,20}$/, 'Please enter a valid phone number'],
    },

    gender: {
      type: String,
      enum: {
        values: ['Male', 'Female', 'Other'],
        message: 'Gender must be Male, Female, or Other',
      },
    },

    dob: {
      type: Date,
    },

    department: {
      type: String,
      trim: true,
      maxlength: [100, 'Department name cannot exceed 100 characters'],
    },

    skills: {
      type: [String],
      default: [],
    },

    // ─── Parent / Guardian Information ───────────────────────────────
    parent_name: {
      type: String,
      trim: true,
      maxlength: [150, 'Parent name cannot exceed 150 characters'],
    },

    parent_email: {
      type: String,
      trim: true,
      lowercase: true,
      match: [/^\S+@\S+\.\S+$/, 'Please enter a valid parent email address'],
    },

    // ─── Status ──────────────────────────────────────────────────────
    status: {
      type: String,
      enum: {
        values: ['Active', 'Inactive', 'Graduated', 'Expelled'],
        message: 'Status must be Active, Inactive, Graduated, or Expelled',
      },
      default: 'Active',
    },

    // ─── Auth Fields (MERN only — not in PHP version) ─────────────────
    password: {
      type: String,
      minlength: [6, 'Password must be at least 6 characters'],
      select: false, // Never return password in queries by default
    },

    isFirstLogin: {
      type: Boolean,
      default: true,
    },
  },
  {
    timestamps: true, // Adds createdAt and updatedAt automatically
    toJSON: { virtuals: true },
    toObject: { virtuals: true },
  }
);

// ─── Indexes ──────────────────────────────────────────────────────────────────
studentSchema.index({ status: 1 });
studentSchema.index({ department: 1 });
studentSchema.index({ student_name: 'text' }); // Full-text search on name

// ─── Virtuals ─────────────────────────────────────────────────────────────────
/**
 * Virtual: age
 * Calculates the student's age from dob
 */
studentSchema.virtual('age').get(function () {
  if (!this.dob) return null;
  const today = new Date();
  const birth = new Date(this.dob);
  let age = today.getFullYear() - birth.getFullYear();
  const m = today.getMonth() - birth.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
  return age;
});

// ─── Pre-Save Hook: Password Hashing ─────────────────────────────────────────
/**
 * Hash password before saving.
 * Only runs if the password field has been modified.
 */
studentSchema.pre('save', async function (next) {
  if (!this.isModified('password') || !this.password) return next();

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
studentSchema.methods.matchPassword = async function (enteredPassword) {
  return bcrypt.compare(enteredPassword, this.password);
};

// ─── Model Export ─────────────────────────────────────────────────────────────
const Student = mongoose.model('Student', studentSchema);

module.exports = Student;
