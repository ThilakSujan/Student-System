/**
 * FeePayment Model
 * Records individual fee payments made by or on behalf of students.
 * Each payment links a student to a FeeStructure (fee assignment).
 */

const mongoose = require('mongoose');

const feePaymentSchema = new mongoose.Schema(
  {
    // ─── References ───────────────────────────────────────────────────
    student: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Student',
      required: [true, 'Student reference is required'],
    },

    fee_assignment: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'FeeStructure',
      required: [true, 'Fee structure/assignment reference is required'],
    },

    recorded_by: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'User',
      required: [true, 'Recorded-by user reference is required'],
    },

    // ─── Payment Details ──────────────────────────────────────────────
    amount_paid: {
      type: mongoose.Schema.Types.Decimal128,
      required: [true, 'Amount paid is required'],
      get: (v) => (v ? parseFloat(v.toString()) : null),
    },

    payment_date: {
      type: Date,
      required: [true, 'Payment date is required'],
      default: Date.now,
    },

    payment_mode: {
      type: String,
      enum: {
        values: ['Cash', 'Cheque', 'Online', 'Bank Transfer', 'Other'],
        message: 'Payment mode must be Cash, Cheque, Online, Bank Transfer, or Other',
      },
      required: [true, 'Payment mode is required'],
    },

    receipt_no: {
      type: String,
      trim: true,
      maxlength: [50, 'Receipt number cannot exceed 50 characters'],
    },

    remarks: {
      type: String,
      trim: true,
      maxlength: [500, 'Remarks cannot exceed 500 characters'],
    },
  },
  {
    timestamps: true, // Adds createdAt and updatedAt automatically
    toJSON: { virtuals: true, getters: true },
    toObject: { virtuals: true, getters: true },
  }
);

// ─── Indexes ──────────────────────────────────────────────────────────────────
feePaymentSchema.index({ student: 1, payment_date: -1 });
feePaymentSchema.index({ fee_assignment: 1 });
feePaymentSchema.index({ payment_date: -1 });
feePaymentSchema.index({ payment_mode: 1 });
feePaymentSchema.index({ receipt_no: 1 }, { sparse: true });

// ─── Pre-Save Hook ────────────────────────────────────────────────────────────
/**
 * Auto-generate receipt number if not provided.
 * Format: RCP-<timestamp><random 4-digit>
 */
feePaymentSchema.pre('save', function (next) {
  if (!this.receipt_no) {
    const ts = Date.now().toString().slice(-8);
    const rand = Math.floor(1000 + Math.random() * 9000);
    this.receipt_no = `RCP-${ts}${rand}`;
  }
  next();
});

// ─── Model Export ─────────────────────────────────────────────────────────────
const FeePayment = mongoose.model('FeePayment', feePaymentSchema);

module.exports = FeePayment;
