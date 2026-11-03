-- Apply once to an existing E-FoodHub database.
-- Existing KVPM shops retain a sensible default; sellers can update the field
-- when editing/re-submitting their shop application.
ALTER TABLE seller_profiles
  ADD COLUMN campus_name VARCHAR(150) NOT NULL DEFAULT 'Kolej Vokasional Pasir Mas'
  AFTER shop_name;
