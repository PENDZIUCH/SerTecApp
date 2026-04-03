export interface Env {
  DB: D1Database;
  JWT_SECRET: string;
  ENVIRONMENT?: string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  password: string;
  phone: string | null;
  job_title: string | null;
  is_active: number;
  roles: string[];
}

export interface WorkOrder {
  id: number;
  customer_id: number;
  equipment_id: number | null;
  wo_number: string;
  title: string;
  description: string | null;
  priority: string;
  status: string;
  assigned_tech_id: number | null;
  scheduled_date: string | null;
  requires_signature: number;
  created_at: string;
  updated_at: string;
}

export interface Customer {
  id: number;
  customer_type: string;
  business_name: string | null;
  first_name: string | null;
  last_name: string | null;
  email: string | null;
  phone: string | null;
  tax_id: string | null;
  address: string | null;
  city: string | null;
  is_active: number;
}
