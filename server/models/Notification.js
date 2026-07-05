const mongoose = require('mongoose');

const notificationSchema = new mongoose.Schema(
  {
    title: {
      type: String,
      required: [true, 'Title is required'],
      trim: true,
      maxlength: [200, 'Title cannot exceed 200 characters'],
    },
    message: {
      type: String,
      required: [true, 'Message is required'],
      trim: true,
    },
    type: {
      type: String,
      enum: ['General', 'Marks', 'Attendance', 'Fee', 'Exam', 'Event', 'Alert', 'System'],
      default: 'General',
    },
    target_role: {
      type: String,
      enum: ['All', 'Students', 'Staff', 'Admin'],
      default: 'All',
    },
    target_class_id: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Class',
      default: null,
    },
    target_student_id: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Student',
      default: null,
    },
    created_by: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'User',
      default: null,
    },
    status: {
      type: String,
      enum: ['Active', 'Inactive'],
      default: 'Active',
    },
    priority: {
      type: String,
      enum: ['Low', 'Medium', 'High', 'Urgent'],
      default: 'Medium',
    },
    expires_at: {
      type: Date,
      default: null,
    },
    attachment_url: {
      type: String,
      default: null,
    },
    read_by: [
      {
        user_id: mongoose.Schema.Types.ObjectId,
        read_at: { type: Date, default: Date.now },
      },
    ],
  },
  {
    timestamps: { createdAt: 'created_at', updatedAt: 'updated_at' },
  }
);

notificationSchema.index({ target_role: 1, status: 1 });
notificationSchema.index({ expires_at: 1 });
notificationSchema.index({ created_by: 1 });

const Notification = mongoose.model('Notification', notificationSchema);
module.exports = Notification;
