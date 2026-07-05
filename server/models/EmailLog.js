const mongoose = require('mongoose');

const emailLogSchema = new mongoose.Schema(
  {
    to: {
      type: String,
      trim: true,
      lowercase: true,
    },
    subject: {
      type: String,
      required: true,
      trim: true,
    },
    type: {
      type: String,
      enum: [
        'registration',
        'account_approved',
        'account_rejected',
        'otp',
        'attendance_alert',
        'low_attendance',
        'marks_published',
        'fee_invoice',
        'report_card',
        'custom',
        'test',
      ],
      default: 'custom',
    },
    status: {
      type: String,
      enum: ['sent', 'failed', 'pending'],
      default: 'pending',
    },
    error_message: {
      type: String,
      default: null,
    },
    sent_by: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'User',
      default: null,
    },
    metadata: {
      type: mongoose.Schema.Types.Mixed,
      default: {},
    },
    retries: {
      type: Number,
      default: 0,
    },
  },
  {
    timestamps: { createdAt: 'created_at', updatedAt: 'updated_at' },
  }
);

emailLogSchema.index({ status: 1, created_at: -1 });
emailLogSchema.index({ to: 1 });

const EmailLog = mongoose.model('EmailLog', emailLogSchema);
module.exports = EmailLog;
