const mongoose = require('mongoose');

const instituteProfileSchema = new mongoose.Schema(
  {
    institute_name: {
      type: String,
      required: [true, 'Institute name is required'],
      trim: true,
    },
    address: {
      type: String,
      trim: true,
      default: '',
    },
    phone: {
      type: String,
      trim: true,
      default: '',
    },
    email: {
      type: String,
      trim: true,
      lowercase: true,
      default: '',
    },
    principal_name: {
      type: String,
      trim: true,
      default: '',
    },
    logo: {
      type: String,
      default: '',
    },
    other_details: {
      type: String,
      trim: true,
      default: '',
    },
  },
  {
    timestamps: true,
  }
);

const InstituteProfile = mongoose.model('InstituteProfile', instituteProfileSchema);
module.exports = InstituteProfile;
