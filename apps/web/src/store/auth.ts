import { create } from 'zustand';
type User={id:string;email:string;name:string;role:string};type AuthState={user:User|null;token:string|null;setAuth:(token:string,user:User)=>void;logout:()=>void};
export const useAuth=create<AuthState>(set=>({user:null,token:localStorage.getItem('nazar_token'),setAuth:(token,user)=>{localStorage.setItem('nazar_token',token);set({token,user});},logout:()=>{localStorage.removeItem('nazar_token');set({token:null,user:null});}}));
