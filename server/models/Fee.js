const mongoose = require('mongoose');

// Fee Category model
const feeCategorySchema = new mongoose.Schema(
  {
    name: {
      type: String,
      required: [true, 'Category name is required'],
      trim: true,
      unique: true,
    },
    description: {
      type: String,
      trim: true,
      default: null,
    },
    is_active: {
      type: Boolean,
      default: true,
    },
  },
  {
    timestamps: { createdAt: 'created_at', updatedAt: 'updated_at' },
  }
);

// Fee Structure model
const feeStructureSchema = new mongoose.Schema(
  {
    category_id: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'FeeCategory',
      required: [true, 'Category is required'],
    },
    class_id: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Class',
      default: null,
    },
    name: {
      type: String,
      required: [true, 'Structure name is required'],
      trim: true,
    },
    amount: {
      type: Number,
      required: [true, 'Amount is required'],
      min: [0, 'Amount cannot be negative'],
    },
    due_date: {
      type: Date,
      default: null,
    },
    academic_year: {
      type: String,
      trim: true,
      default: null,
    },
    frequency: {
      type: String,
      enum: ['One-time', 'Monthly', 'Quarterly', 'Annual', 'Semester'],
      default: 'One-time',
    },
    priority: {
      type: Number,
      default: 1,
      min: 1,
    },
    is_mandatory: {
      type: Boolean,
      default: true,
    },
    is_active: {
      type: Boolean,
      default: true,
    },
    description: {
      type: String,
      trim: true,
      default: null,
    },
  },
  {
    timestamps: { createdAt: 'created_at', updatedAt: 'updated_at' },
  }
);

// Fee Payment model
const feePaymentSchema = new mongoose.Schema(
  {
    student_id: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Student',
      required: [true, 'Student is required'],
    },
    receipt_no: {
      type: String,
      unique: true,
      required: [true, 'Receipt number is required'],
    },
    amount_paid: {
      type: Number,
      required: [true, 'Amount paid is required'],
      min: [0, 'Amount cannot be negative'],
    },
    payment_date: {
      type: Date,
      default: Date.now,
    },
    payment_method: {
      type: String,
      enum: ['Cash', 'Bank Transfer', 'Online', 'Cheque', 'Card', 'UPI', 'Other'],
      default: 'Cash',
    },
    transaction_id: {
      type: String,
      trim: true,
      default: null,
    },
    notes: {
      type: String,
      trim: true,
      default: null,
    },
    collected_by: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'User',
      default: null,
    },
    // Allocations: how payment is split across fee structures
    allocations: [
      {
        fee_structure_id: {
          type: mongoose.Schema.Types.ObjectId,
          ref: 'FeeStructure',
          required: true,
        },
        allocated_amount: {
          type: Number,
          required: true,
          min: 0,
        },
      },
    ],
    academic_year: {
      type: String,
      trim: true,
      default: null,
    },
    status: {
      type: String,
      enum: ['Paid', 'Partial', 'Refunded', 'Cancelled'],
      default: 'Paid',
    },
  },
  {
    timestamps: { createdAt: 'created_at', updatedAt: 'updated_at' },
  }
);

feePaymentSchema.index({ student_id: 1, payment_date: -1 });
feePaymentSchema.index({ receipt_no: 1 });

const FeeCategory = mongoose.model('FeeCategory', feeCategorySchema);
const FeeStructure = mongoose.model('FeeStructure', feeStructureSchema);
const FeePayment = mongoose.model('FeePayment', feePaymentSchema);

module.exports = { FeeCategory, FeeStructure, FeePayment };
